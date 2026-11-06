<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap/staging-demo.php';

$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAILED temporary staging gate: '.$message);
};
$server = [
    'HTTP_HOST'=>'stage.digiti.in',
    'SERVER_NAME'=>'stage.digiti.in',
    'REQUEST_URI'=>'/pmnow/',
    'HTTPS'=>'on',
];
$now = strtotime('2026-11-06T12:00:00+05:30');
$values = pm_temporary_stage_demo_overrides($server, [], $now);
$check(($values['APP_ENV'] ?? '') === 'staging', 'enable only staging environment');
$check(($values['PMNOW_DEMO_DATA_ALLOWED'] ?? '') === 'true', 'enable fixture flag for staging');
$check(($values['APP_URL'] ?? '') === 'https://stage.digiti.in/pmnow', 'canonical staging URL');
$check(($values['ADMIN_DEV_BYPASS'] ?? '') === 'false', 'admin auth stays enabled');
$check(($values['SESSION_SECURE_COOKIE'] ?? '') === 'true', 'secure cookies required');

foreach ([
    'explicit production'=>[$server,['APP_ENV'=>'production']],
    'explicit opt-out'=>[$server,['PMNOW_DEMO_DATA_ALLOWED'=>'false']],
    'wrong configured URL'=>[$server,['APP_URL'=>'https://other.example/pmnow']],
    'wrong configured path'=>[$server,['APP_BASE_PATH'=>'/another-app']],
    'wrong Host'=>[array_replace($server,['HTTP_HOST'=>'production.example']),[]],
    'wrong selected vhost'=>[array_replace($server,['SERVER_NAME'=>'production.example']),[]],
    'wrong application'=>[array_replace($server,['REQUEST_URI'=>'/other/']),[]],
    'insecure request'=>[array_replace($server,['HTTPS'=>'off']),[]],
] as $name=>[$request,$env]) {
    $check(pm_temporary_stage_demo_overrides($request,$env,$now)===[], $name.' must not opt in');
}
$check(
    pm_temporary_stage_demo_overrides($server,[],strtotime('2026-12-01T00:00:00+05:30'))===[],
    'temporary exception must expire after November 30, 2026'
);
echo "Temporary staging bootstrap security checks passed.\n";
