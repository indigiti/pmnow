<?php
$env=getenv('APP_ENV') ?: 'local';
$appUrl=getenv('APP_URL') ?: 'http://127.0.0.1:8080';
$baseRaw=getenv('APP_BASE_PATH');
if($baseRaw===false || trim((string)$baseRaw)===''){
    $baseRaw=(string)(parse_url($appUrl,PHP_URL_PATH)?:'');
}
$basePath='/' . trim((string)$baseRaw,'/');
if($basePath==='/')$basePath='';
return [
    'name'=>'Pune Mirror Now','env'=>$env,'debug'=>filter_var(getenv('APP_DEBUG') ?: ($env==='local'?'true':'false'), FILTER_VALIDATE_BOOL),'url'=>$appUrl,'base_path'=>$basePath,'timezone'=>getenv('APP_TIMEZONE') ?: 'Asia/Kolkata',
    'persistence'=>getenv('PERSISTENCE_DRIVER') ?: 'file','runtime'=>getenv('RUNTIME_DRIVER') ?: 'file','sse_enabled'=>filter_var(getenv('SSE_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL),
    'engine'=>['url'=>getenv('PYTHON_ENGINE_URL') ?: 'http://127.0.0.1:8788','timeout'=>(float)(getenv('PYTHON_ENGINE_TIMEOUT') ?: 20)],
    'intelligence'=>['external_ai'=>filter_var(getenv('INTELLIGENCE_EXTERNAL_AI') ?: 'false', FILTER_VALIDATE_BOOL)],
    'admin'=>['dev_bypass'=>filter_var(getenv('ADMIN_DEV_BYPASS') ?: ($env==='local'?'true':'false'), FILTER_VALIDATE_BOOL),'bootstrap_email'=>getenv('ADMIN_BOOTSTRAP_EMAIL') ?: 'admin@punemirror.local'],
    'redis'=>['host'=>getenv('REDIS_HOST') ?: '127.0.0.1','port'=>(int)(getenv('REDIS_PORT') ?: 6379),'database'=>(int)(getenv('REDIS_DATABASE') ?: 0),'prefix'=>getenv('REDIS_PREFIX') ?: 'pmnow:'],
    'security'=>[
        'app_key'=>getenv('APP_KEY') ?: ($env==='local'?'dev-only-change-this-key-32chars':'') ,
        'session_path'=>$basePath!==''?$basePath:'/',
        'csrf'=>filter_var(getenv('CSRF_ENABLED') ?: 'true',FILTER_VALIDATE_BOOL),
        'secure_cookie'=>filter_var(getenv('SESSION_SECURE_COOKIE') ?: ($env==='production'?'true':'false'),FILTER_VALIDATE_BOOL),
        'hsts'=>filter_var(getenv('HSTS_ENABLED') ?: ($env==='production'?'true':'false'),FILTER_VALIDATE_BOOL),
        'max_request_bytes'=>(int)(getenv('MAX_REQUEST_BYTES') ?: 1048576),
        'login_rate_limit'=>(int)(getenv('LOGIN_RATE_LIMIT') ?: 10),
        'allow_private_source_urls'=>filter_var(getenv('ALLOW_PRIVATE_SOURCE_URLS') ?: 'false',FILTER_VALIDATE_BOOL),
        'csp'=>getenv('CONTENT_SECURITY_POLICY') ?: "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; media-src 'self' https:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'",
    ],
];
