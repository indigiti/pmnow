<?php

declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Services\SearchIndexService;
use PuneMirror\Services\StoryService;

$app=require dirname(__DIR__).'/bootstrap/app.php'; $root=$app['root'];
$store=new JsonStore($root.'/storage/data'); $indexes=new IndexManager($root.'/storage/indexes');
$stories=new FileStoryRepository($store,$indexes); $storyService=new StoryService($stories,new FileMediaRepository($store),$store);
$search=new SearchIndexService($stories,$store,$indexes,$storyService);
echo json_encode($search->rebuild(),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
