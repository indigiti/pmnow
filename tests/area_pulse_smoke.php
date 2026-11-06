<?php
declare(strict_types=1);
use PuneMirror\Core\JsonStore;
use PuneMirror\Services\AreaPulseService;

require dirname(__DIR__).'/bootstrap/app.php';
$base=sys_get_temp_dir().'/pmnow-area-pulse-'.bin2hex(random_bytes(5));
mkdir($base.'/data',0775,true);
$store=new JsonStore($base.'/data');
$check=static function(bool $condition,string $message):void {
    if(!$condition)throw new RuntimeException('Area Pulse: '.$message);
};
try {
    $area=$store->put('locations',['slug'=>'baner','name'=>'Baner']);
    $traffic=$store->put('categories',['slug'=>'traffic','name'=>'Traffic']);
    $civic=$store->put('categories',['slug'=>'civic','name'=>'Civic']);
    $first=$store->put('stories',[
        'status'=>'published','headline'=>'[DEMO] Baner road layout example',
        'type'=>'article','published_at'=>gmdate('c'),
        'location_ids'=>[$area['id'],$area['id']],
        'category_ids'=>[$traffic['id']],
    ]);
    $store->put('stories',[
        'status'=>'hidden','headline'=>'Hidden traffic story',
        'type'=>'article','published_at'=>gmdate('c'),
        'location_ids'=>[$area['id']],'category_ids'=>[$traffic['id']],
    ]);
    $store->put('stories',[
        'status'=>'published','headline'=>'Very old report',
        'type'=>'article','published_at'=>gmdate('c',strtotime('-60 days')),
        'location_ids'=>[$area['id']],'category_ids'=>[$civic['id']],
    ]);
    $service=new AreaPulseService($store);
    $rows=$service->areas(['Baner'],7);
    $baner=array_values(array_filter($rows,static fn($r)=>$r['slug']==='baner'));
    $check(count($baner)===1,'Baner exists in directory');
    $check($baner[0]['stories']===1,'published recent article counted once');
    $check($baner[0]['traffic_reports']===1,'traffic report correctly classified');
    $check($baner[0]['following']===true,'following status reflected');
    $check($baner[0]['latest_headline']==='[DEMO] Baner road layout example','latest article exists');
    $check(count($service->centers())===12,'static centroids available');
    $check($service->totals($rows)['articles']===1,'news counts are not traffic-speed measurements');
    $check(count(array_filter($rows,static fn($r)=>$r['stories']>0))===1,'only Baner has reports');
    echo "Area Pulse local reporting aggregation smoke: PASS\n";
} finally {
    $iterator=new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach($iterator as $row) {
        if($row->isDir())rmdir($row->getPathname());
        else unlink($row->getPathname());
    }
    rmdir($base);
}
