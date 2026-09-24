<?php
declare(strict_types=1);

$public=realpath(__DIR__.'/../public');
if($public===false){http_response_code(500);exit('Public root missing');}
$path=(string)(parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/');
$candidate=realpath($public.'/'.$path);
if($candidate!==false&&is_file($candidate)&&str_starts_with($candidate,$public.DIRECTORY_SEPARATOR)){
    return false;
}
require $public.'/index.php';
