<?php

declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\PythonClient;
use PuneMirror\Core\UuidV7;
use PuneMirror\Repositories\File\FileAnalysisRepository;
use PuneMirror\Repositories\File\FileClusterRepository;
use PuneMirror\Repositories\File\FileContentRepository;
use PuneMirror\Repositories\File\FileEditorialRepository;
use PuneMirror\Repositories\File\FileJobRepository;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileSourceRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Services\AdminAuthService;
use PuneMirror\Services\ContentHubService;
use PuneMirror\Services\EditorialService;
use PuneMirror\Services\NotificationService;
use PuneMirror\Services\SchedulerService;
use PuneMirror\Services\SearchIndexService;
use PuneMirror\Services\SourceService;
use PuneMirror\Services\StoryService;
use PuneMirror\Services\WorkerService;

$app=require dirname(__DIR__).'/bootstrap/app.php'; $root=$app['root']; $config=$app['config'];
$store=new JsonStore($root.'/storage/data'); $indexes=new IndexManager($root.'/storage/indexes');
$storyRepo=new FileStoryRepository($store,$indexes); $mediaRepo=new FileMediaRepository($store); $sourceRepo=new FileSourceRepository($store); $contentRepo=new FileContentRepository($store); $analysisRepo=new FileAnalysisRepository($store); $clusterRepo=new FileClusterRepository($store); $editorialRepo=new FileEditorialRepository($store); $jobRepo=new FileJobRepository($store);
$python=new PythonClient($config['engine']['url'],$config['engine']['timeout']); $sourceService=new SourceService($sourceRepo,$python);
$hub=new ContentHubService($contentRepo,$analysisRepo,$clusterRepo,$mediaRepo,$storyRepo,$sourceService,$store,$python,$config);
$editorial=new EditorialService($contentRepo,$editorialRepo,$hub,$store); $auth=new AdminAuthService($store,$config); $storyService=new StoryService($storyRepo,$mediaRepo,$store); $search=new SearchIndexService($storyRepo,$store,$indexes,$storyService); $notifications=new NotificationService($store); $scheduler=new SchedulerService($sourceRepo,$jobRepo); $worker=new WorkerService($jobRepo,$hub);

$assert=function(bool $ok,string $label):void{ if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);} echo "PASS: $label\n"; };
$admin=$auth->user(); $assert(is_array($admin) && in_array($admin['role']??'', ['super_admin','admin'],true),'admin/RBAC bootstrap available');
$inbox=$editorial->inbox(); $assert(count($inbox)>=3,'editorial inbox contains seeded normalized content');
$ready=array_values(array_filter($inbox,fn($x)=>($x['workflow_status']??'')==='ready')); $assert(count($ready)>=1,'ready content available for approval');
$target=null; foreach($ready as $candidate){$hay=strtolower((string)($candidate['title']??'').' '.(string)($candidate['caption']??'').' '.(string)($candidate['body']??''));if(str_contains($hay,'footpath repair')){$target=$candidate;break;}} $target ??= $ready[0];
$approved=$editorial->act((string)$target['id'],'approve',$admin); $assert(($approved['content']['workflow_status']??'')==='published' && !empty($approved['content']['story_id']),'editorial approval publishes canonical Story');
$assert(count($editorialRepo->actionsForContent((string)$target['id']))>=1,'editorial audit action persisted');
$rebuilt=$search->rebuild(); $assert(($rebuilt['documents']??0)>=9,'search index rebuilt with newly published story');
$hits=$search->query('footpath repair'); $assert(count($hits)>=1,'filesystem search index returns approved story');

// Scheduler/worker using the manual provider (safe zero-item sync).
$manual=null; foreach($sourceRepo->all() as $s){if(($s['provider']??'')==='manual'){$manual=$s;break;}} $assert((bool)$manual,'manual source found');
$manual['enabled']=true; $manual['sync_enabled']=true; $manual['sync_interval']=60; $manual['last_sync_at']=null; $sourceRepo->save($manual);
$queued=$scheduler->tick(); $assert(count($queued)>=1,'scheduler queues due source sync');
$done=$worker->runNext(); $assert(is_array($done) && ($done['status']??'')==='completed','worker executes queued source job');

$reader=$store->put('users',['id'=>UuidV7::generate(),'name'=>'Notification Test Reader','email'=>null,'role'=>'reader','preferences'=>[]]);
$story=$storyRepo->latest(1)[0]??null; $assert((bool)$story,'published story available for notification test');
$store->put('follows',['id'=>UuidV7::generate(),'user_id'=>$reader['id'],'story_id'=>$story['id'],'follow_type'=>'story']);
$n=$notifications->notifyFollowers((string)$story['id'],'story_update','Test story update','Notification pipeline smoke test');
$assert($n>=1 && count($notifications->forUser((string)$reader['id']))>=1,'follow notification persisted');

echo "M6 smoke tests passed.\n";
