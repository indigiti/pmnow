<?php

declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\PythonClient;
use PuneMirror\Repositories\File\FileAnalysisRepository;
use PuneMirror\Repositories\File\FileClusterRepository;
use PuneMirror\Repositories\File\FileContentRepository;
use PuneMirror\Repositories\File\FileJobRepository;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileSourceRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Services\ContentHubService;
use PuneMirror\Services\SchedulerService;
use PuneMirror\Services\SourceService;

$app=require dirname(__DIR__).'/bootstrap/app.php'; $root=$app['root']; $config=$app['config'];
$store=new JsonStore($root.'/storage/data'); $indexes=new IndexManager($root.'/storage/indexes');
$sources=new FileSourceRepository($store); $jobs=new FileJobRepository($store);
$python=new PythonClient($config['engine']['url'],$config['engine']['timeout']);
$sourceService=new SourceService($sources,$python);
$hub=new ContentHubService(new FileContentRepository($store),new FileAnalysisRepository($store),new FileClusterRepository($store),new FileMediaRepository($store),new FileStoryRepository($store,$indexes),$sourceService,$store,$python,$config);
$scheduler=new SchedulerService($sources,$jobs);
$queued=$scheduler->tick();
echo json_encode(['queued'=>count($queued),'jobs'=>$queued],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
