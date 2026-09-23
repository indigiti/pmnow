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

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$root = $app['root']; $config = $app['config'];
$store = new JsonStore($root . '/storage/data');
$python = new PythonClient($config['engine']['url'], $config['engine']['timeout']);
$storyRepo = new FileStoryRepository($store, new IndexManager($root . '/storage/indexes'));
$sourceService = new SourceService(new FileSourceRepository($store), $python);
$hub = new ContentHubService(new FileContentRepository($store), new FileAnalysisRepository($store), new FileClusterRepository($store), new FileMediaRepository($store), $storyRepo, $sourceService, $store, $python, $config);

$result = $hub->manualImport([
    'external_id' => 'manual-demo-' . date('YmdHis'),
    'title' => 'Heavy rain causes traffic diversion near Shivajinagar',
    'body' => 'Pune Traffic Police announced a traffic diversion near Shivajinagar after heavy rain and waterlogging affected the road. Pune Metro services continue normally.',
    'content_type' => 'article',
    'published_at' => gmdate('c'),
], null, true);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
