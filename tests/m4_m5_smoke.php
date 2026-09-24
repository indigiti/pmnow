<?php

declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\PythonClient;
use PuneMirror\Repositories\File\FileAnalysisRepository;
use PuneMirror\Repositories\File\FileClusterRepository;
use PuneMirror\Repositories\File\FileContentRepository;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileSourceRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Services\ContentHubService;
use PuneMirror\Services\SourceService;

$app=require dirname(__DIR__).'/bootstrap/app.php'; $root=$app['root']; $config=$app['config'];
$assert=function(bool $ok,string $message){if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";};
$store=new JsonStore($root.'/storage/data');
$python=new PythonClient($config['engine']['url'],$config['engine']['timeout']);
$assert($python->available(),'FastAPI content engine reachable');
$sourceRepo=new FileSourceRepository($store); $sources=new SourceService($sourceRepo,$python);
$assert(count($sources->all())>=5,'provider source configurations seeded');
$storyRepo=new FileStoryRepository($store,new IndexManager($root.'/storage/indexes'));
$contentRepo=new FileContentRepository($store,new IndexManager($root.'/storage/indexes')); $analysisRepo=new FileAnalysisRepository($store); $clusterRepo=new FileClusterRepository($store);
$hub=new ContentHubService($contentRepo,$analysisRepo,$clusterRepo,new FileMediaRepository($store),$storyRepo,$sources,$store,$python,$config);

$first=$hub->manualImport([
  'external_id'=>'m4m5-smoke-a',
  'title'=>'Heavy rain causes traffic diversion near Shivajinagar',
  'body'=>'Pune Traffic Police announced a traffic diversion near Shivajinagar after heavy rain and waterlogging.',
  'content_type'=>'article','published_at'=>'2026-09-23T17:00:00+05:30'
],null,true);
$assert(($first['content']['id']??'')!=='','normalized content persisted');
$assert(($first['intelligence']['category']['category_slug']??'')!=='','category intelligence returned');
$assert(in_array('shivajinagar',$first['content']['location_slugs']??[],true),'location intelligence detected Shivajinagar');
$assert(($first['story']['id']??'')!=='','published content created Story contract');
$assert(($first['cluster']['id']??'')!=='','story cluster created');

$second=$hub->manualImport([
  'external_id'=>'m4m5-smoke-b',
  'title'=>'Traffic diversion announced in Shivajinagar after heavy rain',
  'body'=>'Heavy rain and waterlogging prompted Pune Traffic Police to announce a road traffic diversion near Shivajinagar.',
  'content_type'=>'article','published_at'=>'2026-09-23T17:02:00+05:30'
],null,false);
$assert(count($second['duplicates']??[])>=1,'duplicate/near-duplicate engine returned match');
$assert(($second['cluster']['id']??'')!=='','second item assigned to a story cluster');
$assert(count($analysisRepo->forContent($first['content']['id']))>=7,'versioned analysis stages persisted');
echo "M4/M5 smoke tests passed.\n";
