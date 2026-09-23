<?php

declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Services\StoryService;

$app=require dirname(__DIR__).'/bootstrap/app.php';$root=$app['root'];
$assert=function(bool $ok,string $message){if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";};
$id=UuidV7::generate();$assert(UuidV7::isValid($id),'UUIDv7 generator/validator');
$store=new JsonStore($root.'/storage/data');$indexes=new IndexManager($root.'/storage/indexes');
$repo=new FileStoryRepository($store,$indexes);$media=new FileMediaRepository($store);$service=new StoryService($repo,$media,$store);
$feed=$service->feed(20);$assert(count($feed)>=6,'feed has seeded stories');
$types=array_column($feed,'type');
foreach(['article','gallery','developing','live','reel'] as $type)$assert(in_array($type,$types,true),"feed contains $type");
$gallery=current(array_filter($feed,fn($s)=>$s['type']==='gallery'));$assert(count($gallery['media']??[])>=4,'gallery media hydrates');
$develop=current(array_filter($feed,fn($s)=>$s['type']==='developing'));$assert(count($develop['updates']??[])>=4,'developing timeline hydrates');
$live=current(array_filter($feed,fn($s)=>$s['type']==='live'));$liveRecord=$service->liveStoryByStory($live['id']);$assert(count($liveRecord['updates']??[])>=5,'live timeline hydrates');
echo "All smoke tests passed.\n";
