<?php
declare(strict_types=1);

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;
use PuneMirror\Services\NotificationService;
use PuneMirror\Services\UtilityService;

$app=require dirname(__DIR__).'/bootstrap/app.php';$root=$app['root'];
$store=new JsonStore($root.'/storage/data');
$notifications=new NotificationService($store);
$utility=new UtilityService($store,$notifications);
$assert=function(bool $ok,string $label):void{if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";};

$reader=$store->put('users',[
    'id'=>UuidV7::generate(),'name'=>'M12 Utility Reader','email'=>null,'role'=>'reader',
    'preferences'=>['city'=>'Pune','areas'=>['Baner'],'channels'=>['Traffic']]
]);

$entity=$utility->createEntity([
    'kind'=>'traffic','name'=>'M12 Baner corridor '.substr((string)$reader['id'],0,8),
    'authority'=>'M12 smoke desk','areas'=>['Baner'],'source_label'=>'M12 test source'
]);
$assert(($entity['kind']??'')==='traffic'&&in_array('Baner',$entity['areas']??[],true),'utility entity persists typed area metadata');

$followed=$utility->toggleFollow((string)$reader['id'],(string)$entity['id']);
$assert($followed&&in_array((string)$entity['id'],$utility->followedEntityIds((string)$reader['id']),true),'reader can follow utility entity');

$update=$utility->createUpdate([
    'entity_id'=>$entity['id'],'title'=>'M12 traffic advisory','summary'=>'Smoke-test utility update',
    'severity'=>'major','area'=>'Baner','source_label'=>'M12 test source','is_public'=>true
]);
$assert(($update['status']??'')==='active'&&($update['follower_notifications']??0)>=1,'utility update notifies entity followers');

$rows=$utility->feed('traffic','Baner',true,50);
$matched=array_values(array_filter($rows,fn($r)=>($r['id']??'')===($update['id']??'')));
$assert(count($matched)===1,'utility feed filters by kind and area');

$personalized=$utility->personalized($reader,50);
$assert(in_array((string)$update['id'],array_column($personalized,'id'),true),'My Pune area preferences shape utility ranking');

$notices=$notifications->forUser((string)$reader['id']);
$assert(count(array_filter($notices,fn($n)=>($n['meta']['utility_update_id']??'')===($update['id']??'')))>=1,'utility notification lands in existing Alerts inbox');

$resolved=$utility->resolveUpdate((string)$update['id']);
$assert(($resolved['status']??'')==='resolved','newsroom can resolve utility update');
$active=$utility->feed('traffic','Baner',true,100);
$assert(!in_array((string)$update['id'],array_column($active,'id'),true),'resolved utility update leaves active feed');

$bad=false;try{$utility->createEntity(['kind'=>'weather','name'=>'Bad source','areas'=>['Pune'],'source_url'=>'file:///etc/passwd']);}catch(Throwable){$bad=true;}
$assert($bad,'utility source URL rejects unsafe schemes');

$metrics=$utility->metrics();
$assert(array_key_exists('entities',$metrics)&&array_key_exists('active_updates',$metrics),'utility desk metrics available');

echo "M12 smoke tests passed.\n";
