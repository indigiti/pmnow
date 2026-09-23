<?php
declare(strict_types=1);

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UrlGuard;
use PuneMirror\Core\UuidV7;
use PuneMirror\Repositories\File\FileJobRepository;
use PuneMirror\Runtime\FileRuntimeStore;
use PuneMirror\Services\CredentialVault;
use PuneMirror\Services\ErrorCenterService;
use PuneMirror\Services\QuotaService;
use PuneMirror\Services\RateLimiter;

$app=require dirname(__DIR__).'/bootstrap/app.php';
$root=$app['root'];$c=$app['config'];$store=new JsonStore($root.'/storage/data');
$assert=function(bool $ok,string $label){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";};

$id=UuidV7::generate();
$vault=new CredentialVault($root.'/storage/secrets',(string)$c['security']['app_key']);
$masked=$vault->put($id,['access_token'=>'super-secret-123456']);
$assert(($vault->all($id)['access_token']??'')==='super-secret-123456','credential vault decrypts encrypted secret');
$assert(str_ends_with($masked['access_token']??'','3456'),'credential vault returns masked value');
$vault->delete($id);

$blocked=false;try{UrlGuard::assertPublicHttpUrl('http://127.0.0.1/admin');}catch(Throwable){$blocked=true;}$assert($blocked,'SSRF guard blocks loopback URL');
$blocked=false;try{UrlGuard::assertPublicHttpUrl('http://169.254.169.254/latest/meta-data');}catch(Throwable){$blocked=true;}$assert($blocked,'SSRF guard blocks cloud metadata address');

$runtime=new FileRuntimeStore($root.'/storage/cache/m7-test');
$limiter=new RateLimiter($runtime);$subject=UuidV7::generate();
$assert($limiter->hit('test',$subject,2,60)['allowed'],'rate limiter permits request 1');
$assert($limiter->hit('test',$subject,2,60)['allowed'],'rate limiter permits request 2');
$assert(!$limiter->hit('test',$subject,2,60)['allowed'],'rate limiter blocks excess request');

$jobs=new FileJobRepository($store);$jid=UuidV7::generate();
$jobs->save(['id'=>$jid,'type'=>'SOURCE_SYNC','subject_id'=>UuidV7::generate(),'status'=>'queued','attempts'=>0,'max_attempts'=>5,'available_at'=>gmdate('c'),'payload'=>[]]);
$claimed=$jobs->claimNext();
$assert(($claimed['id']??'')===$jid&&($claimed['status']??'')==='processing'&&($claimed['attempts']??0)===1,'job repository atomically claims queued work');
$assert($jobs->claimNext()===null,'claimed job is not returned as queued again');
$store->delete('jobs',$jid);

$errors=new ErrorCenterService($store);
$e=$errors->capture('M7_TEST','test error',['token'=>'should-not-persist','safe'=>'yes']);
$assert(($e['context']['token']??'')==='[REDACTED]','Error Center redacts secret context');
$errors->resolve((string)$e['id']);

$quota=new QuotaService($store);$qid=UuidV7::generate();$quota->record($qid,86,100);
$assert(($quota->state($qid,100)['status']??'')==='quota_low','quota service enters quota_low state');

$prodIndex=(string)file_get_contents($root.'/public/index.php');
$routeGate=<<<'PHP'
if(($config['env']??'local')!=='production')$router->post('/api/v1/live/{id}/demo-update'
PHP;
$assert(str_contains($prodIndex,$routeGate),'demo live mutation route is production-gated');
$liveView=(string)file_get_contents($root.'/resources/views/pages/live.php');
$assert(str_contains($liveView,"APP_ENV','local') !== 'production'"),'demo live control is hidden in production');
$adminController=(string)file_get_contents($root.'/app/Controllers/AdminPageController.php');
$assert(str_contains($adminController,"pm_url('/admin/login')"),'admin redirect preserves application base path');
$sourceService=(string)file_get_contents($root.'/app/Services/SourceService.php');
$assert(!str_contains($sourceService,'getenv('),'SourceService uses portable environment access');
$health=(string)file_get_contents($root.'/public/health.php');$ready=(string)file_get_contents($root.'/public/ready.php');
$assert(str_contains($health,'Cache-Control: no-store')&&str_contains($ready,'Cache-Control: no-store'),'health probes disable caching');
$assert(str_contains($ready,"['data','indexes','cache','secrets','events']"),'readiness probes required writable runtime directories');

$views='';$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views',FilesystemIterator::SKIP_DOTS));
foreach($it as $f){if($f->isFile()&&$f->getExtension()==='php')$views.=file_get_contents($f->getPathname());}
$assert(!str_contains($views,'onclick='),'views contain no inline onclick handlers');
$assert(!UrlGuard::isSafeMediaUrl('file:///etc/passwd'),'unsafe media URL scheme rejected');

echo "M7 smoke tests passed.\n";
