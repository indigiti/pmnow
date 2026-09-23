<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$private=dirname(__DIR__,2).'/private_html/pmnow';
$local=dirname(__DIR__);
$root=is_file($local.'/bootstrap/app.php')?$local:$private;
$ok=is_file($root.'/bootstrap/app.php') && is_dir($root.'/storage');
http_response_code($ok?200:503);
echo json_encode(['ok'=>$ok,'app'=>'pmnow','private_runtime'=>$ok?'available':'missing','time'=>gmdate('c')],JSON_UNESCAPED_SLASHES);
