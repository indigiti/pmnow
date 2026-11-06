<?php
declare(strict_types=1);

use PuneMirror\Core\JsonStore;
use PuneMirror\Services\DemoFixtureService;

require dirname(__DIR__) . '/bootstrap/app.php';
pm_env_set('PMNOW_DEMO_DATA_ALLOWED', 'true');

$base = sys_get_temp_dir() . '/pmnow-autoseed-' . bin2hex(random_bytes(6));
mkdir($base . '/storage/data', 0775, true);
mkdir($base . '/storage/indexes', 0775, true);
mkdir($base . '/storage/cache', 0775, true);
$assert = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAIL: ' . $message);
};

$store = new JsonStore($base . '/storage/data');
$existing = $store->put('users', ['name'=>'Pre-existing reader', 'role'=>'reader', 'status'=>'active']);
$staging = [
    'env'=>'staging',
    'url'=>'https://stage.digiti.in/pmnow',
    'base_path'=>'/pmnow',
];
try {
    $prod = new DemoFixtureService($base, array_replace($staging, ['env'=>'production']));
    $assert($prod->ensureStagingSeeded() === false, 'production auto-seed blocked');
    $otherHost = new DemoFixtureService($base, array_replace($staging, ['url'=>'https://example.com/pmnow']));
    $assert($otherHost->ensureStagingSeeded() === false, 'unapproved host auto-seed blocked');
    $otherPath = new DemoFixtureService($base, array_replace($staging, ['base_path'=>'/other']));
    $assert($otherPath->ensureStagingSeeded() === false, 'unapproved path auto-seed blocked');

    $service = new DemoFixtureService($base, $staging);
    $assert($service->ensureStagingSeeded(), 'first staging request populates demo records');
    $assert($service->ensureStagingSeeded() === false, 'subsequent staging request skips import');
    $fixtureStories = array_values(array_filter(
        $store->all('stories'),
        static fn($x) => ($x['demo_fixture_suite'] ?? '') === DemoFixtureService::SUITE,
    ));
    $assert(count($fixtureStories) === 18, '18 test stories created');
    foreach ($fixtureStories as $story) {
        $assert(($story['status'] ?? '') === 'published', 'demo story published without editorial approval');
        $assert(str_starts_with((string)($story['headline'] ?? ''), '[DEMO]'), 'demo label present');
    }
    $assert(count(array_filter(
        $store->all('contents'),
        static fn($x) => ($x['demo_fixture_suite'] ?? '') === DemoFixtureService::SUITE,
    )) === 7, 'draft editorial states available separately');
    $assert(is_file($base . '/storage/cache/demo-fixtures-autoseed-v1.json'), 'persistent initialization marker');
    $service->execute('purge', true);
    $assert(count(array_filter(
        $store->all('stories'),
        static fn($x) => ($x['demo_fixture_suite'] ?? '') === DemoFixtureService::SUITE,
    )) === 0, 'demo data selectively removed');
    $assert($store->get('users', (string)$existing['id']) !== null, 'unrelated reader preserved');
    $assert($service->ensureStagingSeeded() === false, 'manual cleanup is not silently reversed');
    echo "Staging auto-seed, publication, idempotence and cleanup verified.\n";
} finally {
    $walker = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($walker as $item) {
        if ($item->isDir()) rmdir($item->getPathname());
        else unlink($item->getPathname());
    }
    rmdir($base);
}
