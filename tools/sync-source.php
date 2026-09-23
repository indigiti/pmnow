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
$id = $argv[1] ?? '';
if ($id === '') { fwrite(STDERR, "Usage: php tools/sync-source.php <source-uuid> [limit] [publish:0|1]\n"); exit(2); }
$store = new JsonStore($root . '/storage/data');
$storyRepo = new FileStoryRepository($store, new IndexManager($root . '/storage/indexes'));
$sourceService = new SourceService(new FileSourceRepository($store), new PythonClient($config['engine']['url'], $config['engine']['timeout']));
$hub = new ContentHubService(new FileContentRepository($store), new FileAnalysisRepository($store), new FileClusterRepository($store), new FileMediaRepository($store), $storyRepo, $sourceService, $store, new PythonClient($config['engine']['url'], $config['engine']['timeout']), $config);
$result = $hub->syncSource($id, (int)($argv[2] ?? 10), isset($argv[3]) ? (bool)((int)$argv[3]) : null);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
