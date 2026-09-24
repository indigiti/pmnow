<?php
declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Repositories\File\FileUserStateRepository;
use PuneMirror\Services\AnalyticsService;
use PuneMirror\Services\DistributionService;
use PuneMirror\Services\NotificationService;
use PuneMirror\Services\PushSubscriptionService;
use PuneMirror\Services\StoryService;
use PuneMirror\Services\UserStateService;

$app=require dirname(__DIR__).'/bootstrap/app.php';$root=$app['root'];
$assert=function(bool $ok,string $label){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";};
$store=new JsonStore($root.'/storage/data');$indexes=new IndexManager($root.'/storage/indexes');
$stories=new StoryService(new FileStoryRepository($store,$indexes),new FileMediaRepository($store),$store);
$notifications=new NotificationService($store);$analytics=new AnalyticsService($store);
$distribution=new DistributionService($store,$stories,$notifications,$analytics);
$users=new UserStateService(new FileUserStateRepository($store));

$reader=$store->put('users',[
    'id'=>UuidV7::generate(),'name'=>'M11 Reader','email'=>null,'role'=>'reader',
    'preferences'=>['city'=>'Pune','areas'=>['Baner'],'channels'=>['Food']],
    'notification_preferences'=>[
        'enabled'=>true,'breaking'=>true,'following'=>true,'area_alerts'=>true,'topic_alerts'=>true,
        'morning_digest'=>true,'evening_digest'=>false,'weekend_digest'=>false,'timezone'=>'Asia/Kolkata'
    ]
]);
$baner=null;
foreach($stories->feed(50) as $story){
    $areas=array_map('strtolower',array_column($story['locations']??[],'name'));
    if(in_array('baner',$areas,true)){$baner=$story;break;}
}
$assert((bool)$baner,'seeded Baner story available for M11 targeting');

$alert=$distribution->alertStory((string)$baner['id'],'neighbourhood');
$assert(($alert['matched']??0)>=1&&($alert['sent']??0)>=1,'neighbourhood distribution targets opted-in Baner reader');
$again=$distribution->alertStory((string)$baner['id'],'neighbourhood');
$assert(($again['sent']??-1)===0,'story alert delivery is idempotent');

$digest=$distribution->deliverDigest((string)$reader['id'],'morning');
$assert(($digest['delivered']??false)===true&&($digest['story_count']??0)>=1,'morning digest creates reader briefing');
$duplicate=$distribution->deliverDigest((string)$reader['id'],'morning');
$assert(($duplicate['delivered']??true)===false,'same-day digest does not duplicate');

$channel=$distribution->channelFeed(5);
$assert(count($channel)>=1&&!empty($channel[0]['url'])&&str_starts_with((string)$channel[0]['url'],'http'),'channel feed exposes absolute story URLs');

$push=new PushSubscriptionService($store);
$endpoint='https://push.example.test/'.bin2hex(random_bytes(6));
$sub=$push->save((string)$reader['id'],['endpoint'=>$endpoint,'keys'=>['p256dh'=>'test-key','auth'=>'test-auth']]);
$assert(($sub['transport_status']??'')==='registered'&&count($push->forUser((string)$reader['id']))>=1,'push subscription registry persists reader endpoint');
$assert($push->remove((string)$reader['id'],$endpoint),'push subscription registry can remove endpoint');

$current=$users->currentUser();
$updated=$users->updateNotificationPreferences(['breaking'=>false,'morning_digest'=>true,'timezone'=>'Asia/Kolkata']);
$assert(($updated['notification_preferences']['breaking']??true)===false&&($updated['notification_preferences']['morning_digest']??false)===true,'reader notification preferences persist');

$schedulerSource=(string)file_get_contents($root.'/app/Services/SchedulerService.php');
$workerSource=(string)file_get_contents($root.'/app/Services/WorkerService.php');
$assert(str_contains($schedulerSource,'READER_DIGEST_MORNING')&&str_contains($workerSource,'READER_DIGEST_MORNING'),'digest scheduler and worker job contracts are wired');

echo "M11 smoke tests passed.\n";
