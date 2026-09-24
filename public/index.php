<?php

declare(strict_types=1);

use PuneMirror\Controllers\ApiController;
use PuneMirror\Controllers\PageController;
use PuneMirror\Controllers\HubApiController;
use PuneMirror\Controllers\AdminPageController;
use PuneMirror\Controllers\NewsroomApiController;
use PuneMirror\Controllers\DiscoveryController;
use PuneMirror\Controllers\UtilityApiController;
use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\PythonClient;
use PuneMirror\Core\Request;
use PuneMirror\Core\Router;
use PuneMirror\Core\View;
use PuneMirror\Core\SecurityHeaders;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Repositories\File\FileUserStateRepository;
use PuneMirror\Repositories\File\FileSourceRepository;
use PuneMirror\Repositories\File\FileContentRepository;
use PuneMirror\Repositories\File\FileAnalysisRepository;
use PuneMirror\Repositories\File\FileClusterRepository;
use PuneMirror\Repositories\File\FileEditorialRepository;
use PuneMirror\Repositories\File\FileJobRepository;
use PuneMirror\Runtime\FileRuntimeStore;
use PuneMirror\Runtime\RedisClient;
use PuneMirror\Runtime\RedisRuntimeStore;
use PuneMirror\Services\StoryService;
use PuneMirror\Services\UserStateService;
use PuneMirror\Services\SourceService;
use PuneMirror\Services\ContentHubService;
use PuneMirror\Services\AdminAuthService;
use PuneMirror\Services\EditorialService;
use PuneMirror\Services\SearchIndexService;
use PuneMirror\Services\NotificationService;
use PuneMirror\Services\SchedulerService;
use PuneMirror\Services\WorkerService;
use PuneMirror\Services\CredentialVault;
use PuneMirror\Services\QuotaService;
use PuneMirror\Services\ErrorCenterService;
use PuneMirror\Services\RateLimiter;
use PuneMirror\Services\SecurityKernel;
use PuneMirror\Services\SystemHealthService;
use PuneMirror\Services\AnalyticsService;
use PuneMirror\Services\SeoService;
use PuneMirror\Services\PersonalizationService;
use PuneMirror\Services\DistributionService;
use PuneMirror\Services\PushSubscriptionService;
use PuneMirror\Services\UtilityService;

$localRoot=dirname(__DIR__);
$deployedRoot=dirname(__DIR__,2).'/private_html/pmnow';
$root=is_file($localRoot.'/bootstrap/app.php')?$localRoot:$deployedRoot;
if(!is_file($root.'/bootstrap/app.php')){http_response_code(503);header('Content-Type: text/plain; charset=utf-8');exit('PMNow private runtime is not installed.');}
require_once $root.'/bootstrap/env.php';

// Bootstrap-safe handler: catches failures before ErrorCenter and the full service graph exist.
set_exception_handler(function(\Throwable $e): void {
    error_log('PMNow bootstrap exception: '.get_class($e).': '.$e->getMessage());
    if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
    http_response_code(500);
    echo '<h1>500</h1><p>Application bootstrap failed.</p>';
});
if($root===$deployedRoot){
    $defaults=[
        'APP_ENV'=>'production','APP_DEBUG'=>'false','APP_BASE_PATH'=>'/pmnow',
        'PERSISTENCE_DRIVER'=>'file','RUNTIME_DRIVER'=>'file','SSE_ENABLED'=>'false',
        'ADMIN_DEV_BYPASS'=>'false','CSRF_ENABLED'=>'true','SESSION_SECURE_COOKIE'=>'true',
        'HSTS_ENABLED'=>'true','ALLOW_PRIVATE_SOURCE_URLS'=>'false','ENGINE_AUTOSTART'=>'false'
    ];
    foreach($defaults as $k=>$v)if(pm_env($k,null)===null)pm_env_set($k,$v);
    // This application is deployed by DigiOps at a fixed public sub-path.
    // Force it here because some PHP-FPM environments expose blank/root APP_BASE_PATH values.
    pm_env_set('APP_BASE_PATH','/pmnow');
    if(pm_env('APP_URL',null)===null){$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=(string)($_SERVER['HTTP_HOST']??'localhost');pm_env_set('APP_URL',$scheme.'://'.$host.'/pmnow');}
    $configuredKey=(string)pm_env('APP_KEY','');
    if(strlen($configuredKey)<24){
        $keyFile=$root.'/storage/secrets/app.key';
        if(!is_dir(dirname($keyFile)))@mkdir(dirname($keyFile),0700,true);
        $key=is_file($keyFile)?trim((string)@file_get_contents($keyFile)):'';
        if(strlen($key)<24){$key=bin2hex(random_bytes(32));@file_put_contents($keyFile,$key.PHP_EOL,LOCK_EX);@chmod($keyFile,0600);}
        if(strlen($key)>=24)pm_env_set('APP_KEY',$key);
    }
}
$app=require $root.'/bootstrap/app.php';$root=$app['root'];$config=$app['config'];
SecurityHeaders::apply($config);

$store=new JsonStore($root.'/storage/data');$indexes=new IndexManager($root.'/storage/indexes');
$storyRepo=new FileStoryRepository($store,$indexes);$mediaRepo=new FileMediaRepository($store);$userRepo=new FileUserStateRepository($store);$sourceRepo=new FileSourceRepository($store);$contentRepo=new FileContentRepository($store,$indexes);$analysisRepo=new FileAnalysisRepository($store);$clusterRepo=new FileClusterRepository($store);$editorialRepo=new FileEditorialRepository($store);$jobRepo=new FileJobRepository($store);
$errors=new ErrorCenterService($store);$quota=new QuotaService($store);$vault=new CredentialVault($root.'/storage/secrets',(string)($config['security']['app_key']??''));
$python=new PythonClient($config['engine']['url'],$config['engine']['timeout']);
$sourceService=new SourceService($sourceRepo,$python,$vault,$quota,$errors,(bool)($config['security']['allow_private_source_urls']??false));
$hubService=new ContentHubService($contentRepo,$analysisRepo,$clusterRepo,$mediaRepo,$storyRepo,$sourceService,$store,$python,$config);
$authService=new AdminAuthService($store,$config);$editorialService=new EditorialService($contentRepo,$editorialRepo,$hubService,$store);$searchService=new SearchIndexService($storyRepo,$store,$indexes,new StoryService($storyRepo,$mediaRepo,$store));$notificationService=new NotificationService($store);$view=new View($root);
$storyService=new StoryService($storyRepo,$mediaRepo,$store);$userService=new UserStateService($userRepo);$analyticsService=new AnalyticsService($store);$seoService=new SeoService($config);$personalizationService=new PersonalizationService($storyService,$userService);$distributionService=new DistributionService($store,$storyService,$notificationService,$analyticsService);$pushSubscriptionService=new PushSubscriptionService($store);$utilityFreshness=new UtilityFreshnessService();$utilityService=new UtilityService($store,$notificationService,$utilityFreshness);$communityService=new CommunityService($store);$eventService=new EventService($store);$schedulerService=new SchedulerService($sourceRepo,$jobRepo,$store);$workerService=new WorkerService($jobRepo,$hubService,$errors,$distributionService);

$runtime=new FileRuntimeStore($root.'/storage/cache');$redisClient=new RedisClient($config['redis']['host'],$config['redis']['port'],$config['redis']['prefix']);if($config['runtime']==='redis'&&$redisClient->available())$runtime=new RedisRuntimeStore($redisClient);
$healthService=new SystemHealthService($root,$config,$python,$redisClient);
$security=new SecurityKernel(new RateLimiter($runtime),$config);
$request=Request::capture();$security->enforce($request);

set_exception_handler(function(\Throwable $e) use($errors,$config,$request):void{$errors->capture('UNHANDLED_EXCEPTION',$e->getMessage(),['path'=>$request->path,'type'=>get_class($e)]);http_response_code(500);if(str_starts_with($request->path,'/api/')){header('Content-Type: application/json; charset=utf-8');echo json_encode(['data'=>null,'meta'=>(object)[],'errors'=>[['code'=>'INTERNAL_ERROR','message'=>($config['debug']??false)?$e->getMessage():'Internal server error']]]);}else{echo '<h1>500</h1><p>Internal server error.</p>';}});

$page=new PageController($view,$storyService,$userService,$notificationService,$store,$seoService,$personalizationService,$utilityService);$api=new ApiController($storyService,$userService,$store,$runtime,$config,$searchService,$notificationService,$analyticsService,$personalizationService,$pushSubscriptionService);$utilityApi=new UtilityApiController($utilityService,$userService,$analyticsService);$cityApi=new CityApiController($communityService,$eventService,$userService,$analyticsService);$cityPage=new CityPageController($view,$communityService,$eventService,$userService,$seoService);$cityAdminApi=new CityAdminApiController($authService,$communityService,$eventService);$discovery=new DiscoveryController($storyRepo,$storyService,$store,$distributionService);$hub=new HubApiController($sourceService,$hubService,$python,$authService);$adminPage=new AdminPageController($view,$authService,$editorialService,$sourceService,$jobRepo,$store,$errors,$healthService,$analyticsService,$utilityService,$communityService,$eventService);$newsroom=new NewsroomApiController($authService,$editorialService,$jobRepo,$schedulerService,$workerService,$searchService,$notificationService,$userService,$errors,$healthService,$distributionService,$utilityService);$router=new Router();

$router->get('/',fn()=>$page->home());$router->get('/community',fn($r)=>$cityPage->community($r));$router->get('/events',fn($r)=>$cityPage->events($r));$router->get('/near-you',fn()=>$page->nearYou());$router->get('/explore',fn()=>$page->explore());$router->get('/utility',fn($r)=>$page->utility($r));$router->get('/utility/{slug}',fn($r,$p)=>$page->utilityEntity($p['slug']));$router->get('/pune/{slug}',fn($r,$p)=>$page->canonicalStory($p['slug']));$router->get('/category/{slug}',fn($r,$p)=>$page->topic('category',$p['slug']));$router->get('/area/{slug}',fn($r,$p)=>$page->topic('area',$p['slug']));$router->get('/robots.txt',fn()=>$discovery->robots());$router->get('/sitemap.xml',fn()=>$discovery->sitemap());$router->get('/news-sitemap.xml',fn()=>$discovery->newsSitemap());$router->get('/rss.xml',fn()=>$discovery->rss());$router->get('/channels/whatsapp.json',fn()=>$discovery->channel('whatsapp'));$router->get('/channels/telegram.json',fn()=>$discovery->channel('telegram'));$router->get('/story/{id}',fn($r,$p)=>$page->story($p['id']));$router->get('/gallery/{id}',fn($r,$p)=>$page->gallery($p['id']));$router->get('/developing/{id}',fn($r,$p)=>$page->developing($p['id']));$router->get('/live/{id}',fn($r,$p)=>$page->live($p['id']));$router->get('/watch',fn()=>$page->watch());$router->get('/profile',fn()=>$page->profile());$router->get('/notifications',fn()=>$page->notifications());
$router->get('/api/v1/health',fn()=>$api->health());$router->get('/api/v1/community',fn($r)=>$cityApi->community($r));$router->post('/api/v1/community',fn($r)=>$cityApi->submitCommunity($r));$router->post('/api/v1/community/{id}/report',fn($r,$p)=>$cityApi->reportCommunity($r,$p['id']));$router->get('/api/v1/events',fn($r)=>$cityApi->events($r));$router->get('/api/v1/utility',fn($r)=>$utilityApi->feed($r));$router->get('/api/v1/utility/entities',fn($r)=>$utilityApi->entities($r));$router->post('/api/v1/utility/entities/{id}/follow',fn($r,$p)=>$utilityApi->toggleFollow($p['id']));$router->get('/api/v1/feed',fn($r)=>$api->feed($r));$router->post('/api/v1/analytics/events',fn($r)=>$api->analytics($r));$router->patch('/api/v1/me/preferences',fn($r)=>$api->updatePreferences($r));$router->patch('/api/v1/me/notification-preferences',fn($r)=>$api->updateNotificationPreferences($r));$router->post('/api/v1/me/push-subscriptions',fn($r)=>$api->savePushSubscription($r));$router->delete('/api/v1/me/push-subscriptions',fn($r)=>$api->deletePushSubscription($r));$router->get('/api/v1/stories/{id}',fn($r,$p)=>$api->story($p['id']));$router->get('/api/v1/search',fn($r)=>$api->search($r));$router->get('/api/v1/reels',fn()=>$api->reels());$router->get('/api/v1/live/{id}/updates',fn($r,$p)=>$api->liveUpdates($r,$p['id']));$router->get('/api/v1/live/{id}/stream',fn($r,$p)=>$api->stream($p['id']));if(($config['env']??'local')!=='production')$router->post('/api/v1/live/{id}/demo-update',fn($r,$p)=>$api->demoLiveUpdate($p['id']));$router->get('/api/v1/me',fn()=>$api->me());$router->post('/api/v1/stories/{id}/bookmark',fn($r,$p)=>$api->toggleBookmark($p['id']));$router->post('/api/v1/stories/{id}/follow',fn($r,$p)=>$api->toggleFollow($p['id']));$router->get('/api/v1/notifications',fn()=>$newsroom->notifications());$router->post('/api/v1/notifications/{id}/read',fn($r,$p)=>$newsroom->markNotificationRead($p['id']));

$router->get('/admin/login',fn()=>$adminPage->login());$router->get('/admin',fn()=>$adminPage->dashboard());$router->get('/admin/inbox',fn()=>$adminPage->inbox());$router->get('/admin/sources',fn()=>$adminPage->sources());$router->get('/admin/jobs',fn()=>$adminPage->jobs());$router->get('/admin/distribution',fn()=>$adminPage->distribution());$router->get('/admin/utility',fn()=>$adminPage->utility());$router->get('/admin/community',fn()=>$adminPage->community());$router->get('/admin/events',fn()=>$adminPage->events());$router->get('/admin/notifications',fn()=>$adminPage->notifications());$router->get('/admin/system',fn()=>$adminPage->system());$router->get('/admin/errors',fn()=>$adminPage->errors());
$router->post('/api/admin/session',fn($r)=>$newsroom->login($r));$router->get('/api/admin/community',fn()=>$cityAdminApi->communityQueue());$router->post('/api/admin/community/{id}/moderate',fn($r,$p)=>$cityAdminApi->moderate($r,$p['id']));$router->post('/api/admin/events',fn($r)=>$cityAdminApi->createEvent($r));$router->post('/api/admin/logout',fn()=>$newsroom->logout());$router->get('/api/admin/me',fn()=>$newsroom->me());$router->get('/api/admin/dashboard',fn()=>$newsroom->dashboard());$router->get('/api/admin/editorial/inbox',fn($r)=>$newsroom->inbox($r));$router->post('/api/admin/editorial/{id}/action',fn($r,$p)=>$newsroom->act($r,$p['id']));$router->post('/api/admin/editorial/bulk',fn($r)=>$newsroom->bulk($r));$router->get('/api/admin/jobs',fn()=>$newsroom->jobs());$router->post('/api/admin/jobs/scheduler-tick',fn()=>$newsroom->schedulerTick());$router->post('/api/admin/jobs/run-next',fn()=>$newsroom->workerRun());$router->post('/api/admin/distribution/alerts',fn($r)=>$newsroom->distributeAlert($r));$router->post('/api/admin/utility/entities',fn($r)=>$newsroom->createUtilityEntity($r));$router->post('/api/admin/utility/updates',fn($r)=>$newsroom->createUtilityUpdate($r));$router->post('/api/admin/utility/updates/{id}/resolve',fn($r,$p)=>$newsroom->resolveUtilityUpdate($p['id']));$router->post('/api/admin/search/rebuild',fn()=>$newsroom->rebuildSearch());$router->get('/api/admin/system/health',fn()=>$newsroom->systemHealth());$router->get('/api/admin/errors',fn()=>$newsroom->errors());$router->post('/api/admin/errors/{id}/resolve',fn($r,$p)=>$newsroom->resolveError($p['id']));

$router->get('/api/admin/engine/health',fn()=>$hub->engineHealth());$router->get('/api/admin/providers',fn()=>$hub->providers());$router->get('/api/admin/sources',fn()=>$hub->sources());$router->post('/api/admin/sources',fn($r)=>$hub->createSource($r));$router->patch('/api/admin/sources/{id}',fn($r,$p)=>$hub->updateSource($r,$p['id']));$router->delete('/api/admin/sources/{id}',fn($r,$p)=>$hub->deleteSource($p['id']));$router->post('/api/admin/sources/{id}/credentials',fn($r,$p)=>$hub->setCredentials($r,$p['id']));$router->post('/api/admin/sources/{id}/test',fn($r,$p)=>$hub->testSource($p['id']));$router->post('/api/admin/sources/{id}/sync',fn($r,$p)=>$hub->syncSource($r,$p['id']));$router->get('/api/admin/content',fn()=>$hub->content());$router->post('/api/admin/content/import',fn($r)=>$hub->importManual($r));$router->post('/api/admin/content/{id}/analyze',fn($r,$p)=>$hub->analyze($p['id']));$router->get('/api/admin/clusters',fn()=>$hub->clusters());

$router->dispatch($request);
