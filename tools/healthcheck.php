<?php
$app=require dirname(__DIR__).'/bootstrap/app.php';$root=$app['root'];$c=$app['config'];
$ok=true;$checks=['storage'=>is_writable($root.'/storage'),'stories'=>is_dir($root.'/storage/data/stories'),'logs'=>is_dir($root.'/storage/logs')||@mkdir($root.'/storage/logs',0775,true),'openssl'=>extension_loaded('openssl')];
foreach($checks as $n=>$v){echo ($v?'[OK] ':'[FAIL] ').$n.PHP_EOL;$ok=$ok&&$v;}
$engine=rtrim($c['engine']['url'],'/').'/health';$ctx=stream_context_create(['http'=>['timeout'=>1,'ignore_errors'=>true]]);$raw=@file_get_contents($engine,false,$ctx);$engineOk=is_string($raw)&&str_contains($raw,'healthy');echo ($engineOk?'[OK] ':'[FAIL] ')."python engine\n";$ok=$ok&&$engineOk;
exit($ok?0:1);
