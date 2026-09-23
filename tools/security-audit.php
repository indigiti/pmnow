<?php
$app=require dirname(__DIR__).'/bootstrap/app.php';$root=$app['root'];$c=$app['config'];$strict=in_array('--strict',$argv,true);$prod=($c['env']??'local')==='production';
$checks=[
 ['APP_DEBUG is off',!($c['debug']??true),$prod],
 ['ADMIN_DEV_BYPASS is off',!($c['admin']['dev_bypass']??true),$prod],
 ['APP_KEY is strong',strlen((string)($c['security']['app_key']??''))>=32,true],
 ['CSRF enabled',(bool)($c['security']['csrf']??false),true],
 ['secure session cookie',(bool)($c['security']['secure_cookie']??false),$prod],
 ['private source URLs blocked',!($c['security']['allow_private_source_urls']??true),true],
 ['OpenSSL available',extension_loaded('openssl'),true],
 ['storage writable',is_writable($root.'/storage'),true],
 ['secret vault not public',!str_starts_with(realpath($root.'/storage')?:$root.'/storage',realpath($root.'/public')?:$root.'/public'),true],
];
$failed=0;foreach($checks as [$label,$ok,$required]){$state=$ok?'OK':($required?'FAIL':'WARN');echo "[$state] $label\n";if(!$ok&&($required||$strict))$failed++;}
if($prod&&str_starts_with((string)($c['url']??''),'http://')){echo "[FAIL] APP_URL must use HTTPS in production\n";$failed++;}
exit($failed?1:0);
