<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$private=dirname(__DIR__,2).'/private_html/pmnow';
$local=dirname(__DIR__);
$root=is_file($local.'/bootstrap/app.php')?$local:$private;
$probe=function(string $dir):bool{
    if(!is_dir($dir)||!is_writable($dir))return false;
    $file=$dir.'/.ready-'.bin2hex(random_bytes(6)).'.tmp';
    $payload='pmnow-ready-'.bin2hex(random_bytes(8));
    try{
        if(file_put_contents($file,$payload,LOCK_EX)===false)return false;
        return hash_equals($payload,(string)file_get_contents($file));
    }catch(\Throwable){return false;}
    finally{if(is_file($file))@unlink($file);}
};
$checks=['bootstrap'=>is_file($root.'/bootstrap/app.php'),'storage'=>is_dir($root.'/storage')];
foreach(['data','indexes','cache','secrets','events'] as $dir)$checks['write_'.$dir]=$probe($root.'/storage/'.$dir);
$ok=!in_array(false,$checks,true);
http_response_code($ok?200:503);
echo json_encode(['ok'=>$ok,'app'=>'pmnow','checks'=>$checks,'time'=>gmdate('c')],JSON_UNESCAPED_SLASHES);
