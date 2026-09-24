<?php
declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;
use PuneMirror\Repositories\File\FileContentRepository;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Services\AnalyticsService;
use PuneMirror\Services\StoryService;

$app=require dirname(__DIR__).'/bootstrap/app.php';$root=$app['root'];
$assert=function(bool $ok,string $label){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";};
$store=new JsonStore($root.'/storage/data');$indexes=new IndexManager($root.'/storage/indexes');
$stories=new FileStoryRepository($store,$indexes);$service=new StoryService($stories,new FileMediaRepository($store),$store);

$first=$service->feedPage(2);
$assert(count($first['rows'])===2,'cursor feed returns requested page size');
$assert(($first['has_more']??false)===true&&!empty($first['next_cursor']),'cursor feed exposes next cursor');
$second=$service->feedPage(2,(string)$first['next_cursor']);
$ids1=array_column($first['rows'],'id');$ids2=array_column($second['rows'],'id');
$assert(count(array_intersect($ids1,$ids2))===0,'cursor second page does not repeat first page');

$indexes->write('m8-concurrency',['value'=>1]);$indexes->write('m8-concurrency',['value'=>2]);
$assert(($indexes->read('m8-concurrency')['value']??null)===2,'IndexManager atomically replaces index');
$left=glob($root.'/storage/indexes/m8-concurrency.*.tmp')?:[];
$assert(count($left)===0,'IndexManager leaves no temporary files');

$content=new FileContentRepository($store,$indexes);
$source=UuidV7::generate();$external='m8-ext-'.bin2hex(random_bytes(4));
$row=$content->save(['id'=>UuidV7::generate(),'source_id'=>$source,'external_id'=>$external,'title'=>'M8 lookup test']);
$found=$content->findByExternal($source,$external);
$assert(($found['id']??null)===$row['id'],'external content lookup uses derived index');
$store->delete('contents',(string)$row['id']);

$analytics=new AnalyticsService($store);$uid=UuidV7::generate();
$event=$analytics->track($uid,'page_view',['path'=>'/m8-test','ignored'=>['not'=>'scalar']]);
$assert(($event['event']??'')==='page_view'&&($event['properties']['path']??'')==='/m8-test','analytics ledger accepts allowlisted event');
$blocked=false;try{$analytics->track($uid,'arbitrary_event',[]);}catch(Throwable){$blocked=true;}
$assert($blocked,'analytics rejects arbitrary event names');

echo "M8.1 smoke tests passed.\n";
