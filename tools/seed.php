<?php

declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;
use PuneMirror\Repositories\File\FileStoryRepository;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$root = $app['root'];
$dataRoot = $root . '/storage/data';
$environment = strtolower((string)($app['config']['env'] ?? pm_env('APP_ENV',null) ?: 'local'));
$isProduction = $environment === 'production';
$force = in_array('--force', $argv ?? [], true);
$adminPassword = (string)(pm_env('ADMIN_BOOTSTRAP_PASSWORD','') ?: '');

if ($isProduction) {
    if ($adminPassword === '' || strlen($adminPassword) < 12 || hash_equals($adminPassword, 'ChangeMe123!')) {
        fwrite(STDERR, "Production seed requires ADMIN_BOOTSTRAP_PASSWORD with at least 12 characters and no default password.\n");
        exit(2);
    }

    $existing = [];
    foreach (['stories','contents','users','sources','live-stories','live-updates','utility-entities','utility-updates','utility-follows'] as $collection) {
        foreach (glob($dataRoot . '/' . $collection . '/*.json') ?: [] as $file) $existing[] = $file;
    }
    if ($existing && !$force) {
        fwrite(STDERR, "Production storage already contains application data. Refusing destructive seed; pass --force only for an intentional reset.\n");
        exit(3);
    }
}

foreach (glob($dataRoot . '/*/*.json') ?: [] as $file) @unlink($file);
foreach (glob($root . '/storage/indexes/*.json') ?: [] as $file) @unlink($file);

$store = new JsonStore($dataRoot);
$indexes = new IndexManager($root . '/storage/indexes');
$storiesRepo = new FileStoryRepository($store, $indexes);

$make = fn(string $collection, array $data) => $store->put($collection, ['id'=>UuidV7::generate()] + $data);

$adminPassword = $adminPassword !== '' ? $adminPassword : 'ChangeMe123!';
$make('users', [
    'name'=>'Newsroom Admin',
    'email'=>pm_env('ADMIN_BOOTSTRAP_EMAIL','') ?: 'admin@punemirror.local',
    'password_hash'=>password_hash($adminPassword, PASSWORD_DEFAULT),
    'role'=>'super_admin',
    'status'=>'active',
    'preferences'=>[],
]);

$source = $make('sources', ['name'=>'Pune Mirror Desk','provider'=>'manual','handle'=>'@punemirror','enabled'=>true,'sync_enabled'=>false,'sync_interval'=>300,'classification_mode'=>'auto','health_status'=>'live','settings'=>[],'credential_env'=>[]]);
$make('sources', ['name'=>'Pune Mirror WordPress','provider'=>'wordpress','handle'=>'punemirror.com','enabled'=>false,'sync_enabled'=>false,'sync_interval'=>120,'classification_mode'=>'review','health_status'=>'disabled','settings'=>['site_url'=>pm_env('WORDPRESS_SITE_URL','') ?: 'https://punemirror.com'],'credential_env'=>[]]);
$make('sources', ['name'=>'Pune Mirror Instagram','provider'=>'instagram','handle'=>'@punemirror','enabled'=>false,'sync_enabled'=>false,'sync_interval'=>180,'classification_mode'=>'review','health_status'=>'disabled','settings'=>['connection_mode'=>'manual_only','account_id'=>pm_env('INSTAGRAM_ACCOUNT_ID','') ?: ''],'credential_env'=>['access_token'=>'INSTAGRAM_ACCESS_TOKEN']]);
$make('sources', ['name'=>'Pune Mirror YouTube','provider'=>'youtube','handle'=>'Pune Mirror','enabled'=>false,'sync_enabled'=>false,'sync_interval'=>300,'classification_mode'=>'review','health_status'=>'disabled','settings'=>['channel_id'=>pm_env('YOUTUBE_CHANNEL_ID','') ?: ''],'credential_env'=>['api_key'=>'YOUTUBE_API_KEY']]);
$make('sources', ['name'=>'Pune Mirror X','provider'=>'x','handle'=>'@PuneMirror','enabled'=>false,'sync_enabled'=>false,'sync_interval'=>300,'classification_mode'=>'review','health_status'=>'disabled','settings'=>['connection_mode'=>'limited','user_id'=>pm_env('X_USER_ID','') ?: ''],'credential_env'=>['bearer_token'=>'X_BEARER_TOKEN']]);

$cats=[];
foreach ([['Traffic','traffic'],['Civic','civic'],['Metro','metro'],['Weather','weather'],['Crime','crime'],['Education','education'],['Health','health'],['Business','business'],['Lifestyle','lifestyle'],['Events','events'],['Sports','sports']] as [$name,$slug]) $cats[$slug]=$make('categories',['name'=>$name,'slug'=>$slug]);
$locs=[];
foreach ([['Pune','pune',['Pune City']],['Shivajinagar','shivajinagar',['Shivaji Nagar']],['Koregaon Park','koregaon-park',['KP']],['Pune Riverfront','riverfront',['Riverfront']],['Baner','baner',[]],['Aundh','aundh',[]],['Kothrud','kothrud',[]],['Hadapsar','hadapsar',[]],['Viman Nagar','viman-nagar',[]],['Wakad','wakad',[]],['Katraj','katraj',[]]] as [$name,$slug,$aliases]) $locs[$slug]=$make('locations',['name'=>$name,'slug'=>$slug,'city'=>'Pune','aliases'=>$aliases]);

$make('rules', ['name'=>'Traffic Police advisories','enabled'=>true,'contains_any'=>['traffic diversion','road closed','वाहतूक बदल'],'category_slug'=>'traffic','priority'=>'high']);
$make('rules', ['name'=>'Rain and waterlogging','enabled'=>true,'contains_any'=>['waterlogging','heavy rain','पाऊस'],'category_slug'=>'weather','priority'=>'high']);

$media=[];
$addMedia=function(string $key,string $file,int $pos=1,string $caption='') use (&$media,$make){
    $media[$key]=$make('media',['type'=>'image','url'=>'/media/'.$file,'caption'=>$caption,'credit'=>'Pune Mirror concept','position'=>$pos]);
};
$addMedia('rain','home_rain.jpg');
$addMedia('fort','home_fort.jpg');
$addMedia('river1','gallery_river.jpg',1,'A look at the riverfront stretch');
$addMedia('river2','explore_baner.jpg',2,'Public spaces and landscaped edges');
$addMedia('river3','explore_aundh.jpg',3,'Pedestrian-friendly public realm');
$addMedia('river4','explore_kothrud.jpg',4,'Evening activity along the stretch');
$addMedia('metro','develop_metro.jpg');
$addMedia('liveRain','live_rain.jpg');
$addMedia('liveRoad','live_road.jpg');
$addMedia('liveMetro','live_metro.jpg');
$addMedia('liveBus','live_bus.jpg');
$addMedia('kp','reel_kp.jpg');
$addMedia('food','topic_food.jpg');
$addMedia('sports','topic_sports.jpg');

$published = fn(int $minutesAgo) => (new DateTimeImmutable('2026-09-23T16:20:00+05:30'))->modify("-$minutesAgo minutes")->format(DATE_ATOM);
$story=function(array $data) use($storiesRepo,$source){
    return $storiesRepo->save(['id'=>UuidV7::generate(),'status'=>'published','source_id'=>$source['id'],'author'=>'Pune Mirror Desk','tags'=>[]] + $data);
};

$rain=$story([
 'type'=>'article','headline'=>'Heavy rain slows traffic across Pune','deck'=>'Waterlogging and slow-moving traffic affect several key corridors as showers continue across the city.','body'=>"Heavy rain across Pune slowed traffic through several arterial roads during the evening commute. Local authorities advised motorists to use main corridors and allow additional travel time.\n\nThis demo story shows how a standard Pune Mirror article opens from the visual Home feed while keeping the full report, location and actions in one canonical Story object.",'media_ids'=>[$media['rain']['id']],'category_ids'=>[$cats['traffic']['id'],$cats['weather']['id']],'location_ids'=>[$locs['pune']['id']],'published_at'=>$published(12),'display_time'=>'12 min ago','likes'=>'2.4K','comments'=>'143'
]);
$fort=$story([
 'type'=>'article','headline'=>'Shaniwar Wada restoration enters a new phase','deck'=>'Conservation work focuses on visitor movement, stonework and improved interpretation of the historic site.','body'=>"A fresh phase of conservation work is underway around Shaniwar Wada. The project is expected to improve circulation and interpretation while respecting the historic fabric.\n\nThe card is deliberately visual-first so local reporting can be consumed quickly and opened for the complete context when needed.",'media_ids'=>[$media['fort']['id']],'category_ids'=>[$cats['civic']['id']],'location_ids'=>[$locs['shivajinagar']['id']],'published_at'=>$published(38),'display_time'=>'38 min ago','likes'=>'1.8K','comments'=>'91'
]);
$gallery=$story([
 'type'=>'gallery','headline'=>'Inside Pune’s newly opened riverfront stretch','deck'=>'Swipe through the new public realm, promenades and river-edge improvements in this photo story.','body'=>'The gallery experience is designed as a mobile-native photo story. Users can swipe horizontally through media while the headline, caption, byline and story context remain immediately available below.','media_ids'=>[$media['river1']['id'],$media['river2']['id'],$media['river3']['id'],$media['river4']['id']],'category_ids'=>[$cats['civic']['id'],$cats['lifestyle']['id']],'location_ids'=>[$locs['riverfront']['id']],'published_at'=>$published(61),'display_time'=>'1 hr ago','likes'=>'3.1K','comments'=>'204'
]);
$develop=$story([
 'type'=>'developing','headline'=>'Pune Metro service disruption: what we know so far','deck'=>'A developing timeline brings every verified update into one story instead of scattering updates across the feed.','body'=>'This story stays open while the event develops. New verified updates are added chronologically and readers can follow the story for future alerts.','media_ids'=>[$media['metro']['id']],'category_ids'=>[$cats['metro']['id']],'location_ids'=>[$locs['shivajinagar']['id']],'published_at'=>$published(88),'display_time'=>'Developing','likes'=>'4.9K','comments'=>'311'
]);
$live=$story([
 'type'=>'live','headline'=>'Pune rain LIVE: traffic, Metro and road updates','deck'=>'Follow verified citywide updates as rain affects roads, buses and local travel.','body'=>'A live story uses incremental updates so readers receive new information without reloading the complete page.','media_ids'=>[$media['liveRain']['id']],'category_ids'=>[$cats['weather']['id'],$cats['traffic']['id']],'location_ids'=>[$locs['pune']['id']],'published_at'=>$published(4),'display_time'=>'LIVE NOW','likes'=>'8.2K','comments'=>'522'
]);
$reel1=$story([
 'type'=>'reel','headline'=>'Waterlogging reported near Koregaon Park','deck'=>'A quick visual update from the road as evening rain intensifies. Tap the headline to read the full context.','body'=>'Short visual updates use the same Story contract as articles. The Watch experience only changes the renderer, not the editorial object.','media_ids'=>[$media['kp']['id']],'category_ids'=>[$cats['traffic']['id'],$cats['weather']['id']],'location_ids'=>[$locs['koregaon-park']['id']],'published_at'=>$published(7),'display_time'=>'7 min ago','likes'=>'6.7K','comments'=>'420'
]);
$reel2=$story([
 'type'=>'reel','headline'=>'Metro services continue despite showers','deck'=>'Commuters shift to Metro on rain-hit evening corridors.','body'=>'This second visual card demonstrates vertical native scroll snapping in the Watch feed.','media_ids'=>[$media['liveMetro']['id']],'category_ids'=>[$cats['metro']['id']],'location_ids'=>[$locs['shivajinagar']['id']],'published_at'=>$published(21),'display_time'=>'21 min ago','likes'=>'3.9K','comments'=>'194'
]);
$reel3=$story([
 'type'=>'reel','headline'=>'Pune food street gets a rainy-evening rush','deck'=>'A lighter local update in the same vertical Watch experience.','body'=>'The feed can mix hard news, lifestyle and service journalism while preserving clear category and source labels.','media_ids'=>[$media['food']['id']],'category_ids'=>[$cats['lifestyle']['id']],'location_ids'=>[$locs['baner']['id']],'published_at'=>$published(44),'display_time'=>'44 min ago','likes'=>'5.2K','comments'=>'267'
]);

foreach ([
 ['11:12','Normal service resumes','Metro services are reported normal across the affected corridor.'],
 ['10:31','Partial service restored','Trains resume on selected sections while checks continue.'],
 ['10:05','Officials issue a service statement','Commuters are asked to follow station announcements and official updates.'],
 ['09:42','Initial disruption reported','A temporary operational issue affects movement on part of the corridor.'],
] as [$time,$headline,$body]) {
 $make('story-updates',['story_id'=>$develop['id'],'headline'=>$headline,'body'=>$body,'published_at'=>'2026-09-23T'.$time.':00+05:30']);
}

$liveStory=$make('live-stories',['story_id'=>$live['id'],'status'=>'live','started_at'=>'2026-09-23T14:30:00+05:30','ended_at'=>null]);
foreach ([
 ['16:18','traffic','Slow traffic persists on arterial roads','Movement remains slow in a few low-lying pockets; drivers are advised to use main roads.'],
 ['16:02','metro','Metro services operating normally','Metro services continue normally and are seeing increased commuter demand.'],
 ['15:47','bus','PMPML diverts selected buses','Temporary route changes are in place where waterlogging affects bus movement.'],
 ['15:31','road','Water receding on key stretches','Civic teams report improving conditions on several previously waterlogged roads.'],
 ['15:10','weather','Heavy showers continue across the city','Short intense spells of rain continue in multiple Pune neighbourhoods.'],
] as [$time,$type,$headline,$body]) {
 $make('live-updates',['live_story_id'=>$liveStory['id'],'type'=>$type,'headline'=>$headline,'body'=>$body,'published_at'=>'2026-09-23T'.$time.':00+05:30']);
}

// M6 editorial inbox fixtures. These use the normalized Content contract and are intentionally unpublished.
foreach ([
 ['external_id'=>'m6-review-traffic','content_type'=>'article','title'=>'Traffic restrictions proposed near University Road','body'=>'A traffic advisory proposes temporary diversions near University Road during civic work.','caption'=>'Traffic advisory awaiting editorial review.','workflow_status'=>'review_required','final_category_slugs'=>['traffic'],'final_location_slugs'=>['shivajinagar']],
 ['external_id'=>'m6-ready-civic','content_type'=>'article','title'=>'PMC begins neighbourhood footpath repair drive','body'=>'Civic teams have started a phased footpath repair programme in selected neighbourhoods.','caption'=>'Civic update ready for newsroom approval.','workflow_status'=>'ready','final_category_slugs'=>['civic'],'final_location_slugs'=>['pune']],
 ['external_id'=>'m6-ready-event','content_type'=>'article','title'=>'Weekend public event calendar expands across Pune','body'=>'A set of community events is scheduled across the city this weekend.','caption'=>'Events desk item ready for review.','workflow_status'=>'ready','final_category_slugs'=>['events'],'final_location_slugs'=>['pune']],
] as $row) {
    $make('contents', $row + [
        'source_id'=>$source['id'],'provider'=>'manual','source_handle'=>'@punemirror','permalink'=>null,
        'published_at'=>$published(5),'discovered_at'=>gmdate('c'),'modified_at'=>gmdate('c'),'language'=>'en','media_ids'=>[],'entity_names'=>[],
    ]);
}


// M12 utility fixtures. These are explicit demo records for local/CI certification only.
$utilityTraffic=$make('utility-entities',[
    'kind'=>'traffic','name'=>'University Road traffic corridor','slug'=>'university-road-traffic',
    'authority'=>'Pune traffic desk demo','areas'=>['Shivajinagar','Pune'],'status'=>'active',
    'source_label'=>'PMNow demo seed','source_url'=>null,'metadata'=>['demo'=>true],
]);
$utilityMetro=$make('utility-entities',[
    'kind'=>'transit','name'=>'Pune Metro network','slug'=>'pune-metro-network',
    'authority'=>'Metro service demo','areas'=>['Pune','Shivajinagar'],'status'=>'active',
    'source_label'=>'PMNow demo seed','source_url'=>null,'metadata'=>['demo'=>true],
]);
$utilityWeather=$make('utility-entities',[
    'kind'=>'weather','name'=>'Pune weather desk','slug'=>'pune-weather-desk',
    'authority'=>'Weather desk demo','areas'=>['Pune','Citywide'],'status'=>'active',
    'source_label'=>'PMNow demo seed','source_url'=>null,'metadata'=>['demo'=>true],
]);

$make('utility-updates',[
    'entity_id'=>$utilityTraffic['id'],'title'=>'Demo: University Road lane advisory','summary'=>'Demo utility record used to certify the M12 traffic status surface.',
    'severity'=>'advisory','status'=>'active','area'=>'Shivajinagar','published_at'=>$published(9),'verified_at'=>$published(8),
    'source_label'=>'PMNow demo seed','source_url'=>null,'is_public'=>true,'metadata'=>['demo'=>true],
]);
$make('utility-updates',[
    'entity_id'=>$utilityMetro['id'],'title'=>'Demo: Metro service operating update','summary'=>'Demo utility record used to certify transit status cards and entity follows.',
    'severity'=>'info','status'=>'active','area'=>'Pune','published_at'=>$published(16),'verified_at'=>$published(15),
    'source_label'=>'PMNow demo seed','source_url'=>null,'is_public'=>true,'metadata'=>['demo'=>true],
]);
$make('utility-updates',[
    'entity_id'=>$utilityWeather['id'],'title'=>'Demo: City weather advisory','summary'=>'Demo utility record used to certify weather and citywide relevance.',
    'severity'=>'major','status'=>'active','area'=>'Pune','published_at'=>$published(6),'verified_at'=>$published(5),
    'source_label'=>'PMNow demo seed','source_url'=>null,'is_public'=>true,'metadata'=>['demo'=>true],
]);

$storiesRepo->rebuildIndexes();

echo "Seed complete\n";
echo "Home story: {$rain['id']}\nGallery: {$gallery['id']}\nDeveloping: {$develop['id']}\nLive: {$live['id']}\nLive timeline: {$liveStory['id']}\n";
