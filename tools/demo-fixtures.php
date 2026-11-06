<?php
declare(strict_types=1);

/**
 * Optional CLI compatibility wrapper.
 * The Newsroom -> Demo Data page is the recommended interface.
 */
use PuneMirror\Services\DemoFixtureService;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$args = $argv ?? [];
$apply = in_array('--apply', $args, true);
$purge = in_array('--purge', $args, true);
if ($apply === $purge) {
    fwrite(STDERR, "Specify exactly one of --apply or --purge.\n");
    exit(2);
}
$env = strtolower((string)($app['config']['env'] ?? 'production'));
if (!in_array($env, ['local','testing','staging'], true)) {
    fwrite(STDERR, "Demo fixtures are disabled in production.\n");
    exit(3);
}
if ($env === 'staging' && (
    !in_array('--allow-staging', $args, true)
    || !filter_var(pm_env('PMNOW_DEMO_DATA_ALLOWED', false), FILTER_VALIDATE_BOOL)
)) {
    fwrite(STDERR, "Staging CLI requires --allow-staging and PMNOW_DEMO_DATA_ALLOWED=true.\n");
    exit(4);
}

try {
    $fixtures = new DemoFixtureService($app['root'], $app['config']);
    $result = $fixtures->execute($apply ? 'apply' : 'purge');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Demo fixture operation failed: " . $e->getMessage() . "\n");
    exit(5);
}
