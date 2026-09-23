<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
$private=dirname(__DIR__,2).'/private_html/pmnow';
$local=dirname(__DIR__);
$root=is_file($local.'/bootstrap/app.php')?$local:$private;
$checks=['bootstrap'=>is_file($root.'/bootstrap/app.php'),'storage'=>is_dir($root.'/storage'),'storage_writable'=>is_dir($root.'/storage')&&is_writable($root.'/storage')];
$ok=!in_array(false,$checks,true);
http_response_code($ok?200:503);
echo json_encode(['ok'=>$ok,'app'=>'pmnow','checks'=>$checks,'time'=>gmdate('c')],JSON_UNESCAPED_SLASHES);
