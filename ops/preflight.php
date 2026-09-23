<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$fail=[];
foreach(['public/index.php','public/.htaccess','public/health.php','public/ready.php','bootstrap/app.php','config/app.php','tools/seed.php','engine/main.py','VERSION'] as $file){if(!is_file($root.'/'.$file))$fail[]='missing:'.$file;}
$env=(string)@file_get_contents($root.'/.env.production.example');
foreach(['APP_ENV=production','APP_BASE_PATH=/pmnow','ADMIN_DEV_BYPASS=false','SESSION_SECURE_COOKIE=true'] as $needle){if(!str_contains($env,$needle))$fail[]='production-env:'.$needle;}
$public=(string)@file_get_contents($root.'/public/index.php');
if(!str_contains($public,"private_html/pmnow"))$fail[]='front-controller-private-discovery';
if($fail){fwrite(STDERR,"PMNow preflight failed:\n - ".implode("\n - ",$fail)."\n");exit(1);}echo "PMNow preflight OK\n";
