<?php
$env=pm_env('APP_ENV') ?: 'local';
$appUrl=pm_env('APP_URL') ?: 'http://127.0.0.1:8080';
$baseRaw=pm_env('APP_BASE_PATH');
if($baseRaw===null || trim((string)$baseRaw)===''){
    $baseRaw=(string)(parse_url($appUrl,PHP_URL_PATH)?:'');
}
$basePath='/' . trim((string)$baseRaw,'/');
if($basePath==='/')$basePath='';
return [
    'name'=>'Pune Mirror Now','env'=>$env,'debug'=>filter_var(pm_env('APP_DEBUG') ?: ($env==='local'?'true':'false'), FILTER_VALIDATE_BOOL),'url'=>$appUrl,'base_path'=>$basePath,'timezone'=>pm_env('APP_TIMEZONE') ?: 'Asia/Kolkata',
    'persistence'=>pm_env('PERSISTENCE_DRIVER') ?: 'file','runtime'=>pm_env('RUNTIME_DRIVER') ?: 'file','sse_enabled'=>filter_var(pm_env('SSE_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL),
    'engine'=>['url'=>pm_env('PYTHON_ENGINE_URL') ?: 'http://127.0.0.1:8788','timeout'=>(float)(pm_env('PYTHON_ENGINE_TIMEOUT') ?: 20)],
    'intelligence'=>['external_ai'=>filter_var(pm_env('INTELLIGENCE_EXTERNAL_AI') ?: 'false', FILTER_VALIDATE_BOOL)],
    'admin'=>['dev_bypass'=>filter_var(pm_env('ADMIN_DEV_BYPASS') ?: ($env==='local'?'true':'false'), FILTER_VALIDATE_BOOL),'bootstrap_email'=>pm_env('ADMIN_BOOTSTRAP_EMAIL') ?: 'admin@punemirror.local'],
    'redis'=>['host'=>pm_env('REDIS_HOST') ?: '127.0.0.1','port'=>(int)(pm_env('REDIS_PORT') ?: 6379),'database'=>(int)(pm_env('REDIS_DATABASE') ?: 0),'prefix'=>pm_env('REDIS_PREFIX') ?: 'pmnow:'],
    'security'=>[
        'app_key'=>pm_env('APP_KEY') ?: ($env==='local'?'dev-only-change-this-key-32chars':'') ,
        'session_path'=>$basePath!==''?$basePath:'/',
        'csrf'=>filter_var(pm_env('CSRF_ENABLED') ?: 'true',FILTER_VALIDATE_BOOL),
        'secure_cookie'=>filter_var(pm_env('SESSION_SECURE_COOKIE') ?: ($env==='production'?'true':'false'),FILTER_VALIDATE_BOOL),
        'hsts'=>filter_var(pm_env('HSTS_ENABLED') ?: ($env==='production'?'true':'false'),FILTER_VALIDATE_BOOL),
        'max_request_bytes'=>(int)(pm_env('MAX_REQUEST_BYTES') ?: 1048576),
        'login_rate_limit'=>(int)(pm_env('LOGIN_RATE_LIMIT') ?: 10),
        'allow_private_source_urls'=>filter_var(pm_env('ALLOW_PRIVATE_SOURCE_URLS') ?: 'false',FILTER_VALIDATE_BOOL),
        'csp'=>pm_env('CONTENT_SECURITY_POLICY') ?: "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; media-src 'self' https:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'",
    ],
];
