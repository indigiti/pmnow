<?php
declare(strict_types=1);
use PuneMirror\Services\DemoFixtureService;
$app=require dirname(__DIR__).'/bootstrap/app.php';
$local=new DemoFixtureService($app['root'],array_replace($app['config'],['env'=>'local']));
$production=new DemoFixtureService($app['root'],array_replace($app['config'],['env'=>'production']));
$blocked=false;
try{$production->execute('apply');}
catch(RuntimeException $e){$blocked=str_contains($e->getMessage(),'production');}
if(!$blocked){fwrite(STDERR,"FAIL: production demo write not blocked\n");exit(1);}
$blocked=false;
try{$production->execute('purge');}
catch(RuntimeException $e){$blocked=str_contains($e->getMessage(),'production');}
if(!$blocked){fwrite(STDERR,"FAIL: production demo cleanup not blocked\n");exit(1);}
echo "Production demo service gates passed.\n";
