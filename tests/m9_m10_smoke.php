<?php
declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Repositories\File\FileUserStateRepository;
use PuneMirror\Services\PersonalizationService;
use PuneMirror\Services\SeoService;
use PuneMirror\Services\StoryService;
use PuneMirror\Services\UserStateService;

$app=require dirname(__DIR__).'/bootstrap/app.php';$root=$app['root'];$config=$app['config'];
$assert=function(bool $ok,string $label){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";};

$store=new JsonStore($root.'/storage/data');$indexes=new IndexManager($root.'/storage/indexes');
$repo=new FileStoryRepository($store,$indexes);$stories=new StoryService($repo,new FileMediaRepository($store),$store);
$users=new UserStateService(new FileUserStateRepository($store));$personal=new PersonalizationService($stories,$users);$seo=new SeoService($config);

$feed=$stories->feed(20);$assert(count($feed)>=6,'M9/M10 feed available');
$story=$feed[0];
$assert(str_contains((string)($story['path']??''),'/pune/')&&str_contains((string)$story['path'],'--'.$story['id']),'canonical story path contains slug and UUID');

$meta=$seo->story($story);
$assert(($meta['json_ld']['@type']??'')==='NewsArticle','NewsArticle structured data generated');
$assert(str_contains((string)$meta['canonical'],'/pune/')&&str_contains((string)$meta['canonical'],'--'.$story['id']),'story canonical URL is SEO-friendly');
$assert(($meta['robots']??'')==='index,follow,max-image-preview:large','Discover image preview robots directive enabled');

$traffic=$stories->taxonomyFeed('category','traffic',20);
$assert(count($traffic)>=1,'category landing feed resolves published traffic stories');

$updated=$users->updatePreferences(['areas'=>['Baner','Aundh'],'channels'=>['Lifestyle','Traffic']]);
$assert(in_array('Baner',$updated['preferences']['areas']??[],true)&&in_array('Lifestyle',$updated['preferences']['channels']??[],true),'My Pune preferences persist');

$ranked=$personal->forYouPage(5);
$assert(count($ranked['rows']??[])>=1,'personalized For You feed returns stories');
$near=$personal->nearYouPage(10);
$assert(count($near['rows']??[])>=1,'Near You returns stories matching selected neighbourhoods');
$reasons=array_merge(...array_map(fn($s)=>(array)($s['_personalization']['reasons']??[]),$ranked['rows']));
$assert(in_array('near_you',$reasons,true)||in_array('your_topic',$reasons,true),'personalization ranking records neighbourhood/topic reason');

$analytics=new \PuneMirror\Services\AnalyticsService($store);
$analytics->track((string)$updated['id'],'near_you_open',['path'=>'/near-you']);
$summary=$analytics->newsroomSummary();
$assert(array_key_exists('engaged_readers',$summary)&&array_key_exists('personalized_readers',$summary),'newsroom reader analytics summary available');

echo "M9/M10 smoke tests passed.\n";
