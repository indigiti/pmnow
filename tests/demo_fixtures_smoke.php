<?php
/** PMNow demo fixture integration assertions, run only AFTER tools/demo-fixtures.php --apply. */
declare(strict_types=1);
use PuneMirror\Core\JsonStore;
$app=require dirname(__DIR__).'/bootstrap/app.php';
$store=new JsonStore($app['root'].'/storage/data');
$required=['stories'=>18,'utility-entities'=>8,'utility-updates'=>9,'contents'=>7,'users'=>2,'jobs'=>2];
foreach($required as $collection=>$min){
    $records=array_values(array_filter($store->all($collection),static fn($r)=>($r['demo_fixture_suite']??'')==='pmnow-safe-demo-v1'));
    if(count($records)!==$min){fwrite(STDERR,"FAIL: $collection expected $min, got ".count($records)."\n");exit(1);}
    $keys=array_column($records,'demo_fixture_key');
    if(count(array_unique($keys))!==count($keys)){fwrite(STDERR,"FAIL: duplicate fixture keys in $collection\n");exit(1);}
}
foreach($store->all('utility-updates') as $u){
    if(($u['demo_fixture_suite']??'')!=='pmnow-safe-demo-v1')continue;
    if(($u['verified_at']??null)!==null){fwrite(STDERR,"FAIL: demo utility verification must be null\n");exit(1);}
}
foreach($store->all('jobs') as $j){
    if(($j['demo_fixture_suite']??'')!=='pmnow-safe-demo-v1')continue;
    if(in_array($j['status']??'', ['queued','processing'],true)){fwrite(STDERR,"FAIL: fixture job is runnable\n");exit(1);}
}
echo "Demo fixture certification passed.\n";
