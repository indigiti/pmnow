<?php
declare(strict_types=1);

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Repositories\File\FileMediaRepository;
use PuneMirror\Repositories\File\FileStoryRepository;
use PuneMirror\Services\SeoService;
use PuneMirror\Services\StoryService;

$app=require dirname(__DIR__).'/bootstrap/app.php';
$root=sys_get_temp_dir().'/pmnow-sample-seal-'.bin2hex(random_bytes(5));
mkdir($root.'/data',0775,true);
mkdir($root.'/indexes',0775,true);
$assert=static function(bool $test,string $message):void {
    if(!$test)throw new RuntimeException('Sample badge: '.$message);
};

try {
    $store=new JsonStore($root.'/data');
    $stories=new StoryService(
        new FileStoryRepository($store,new IndexManager($root.'/indexes')),
        new FileMediaRepository($store),
        $store
    );
    $data=[
        'id'=>'0199b3f8-b849-7d73-a90d-178ea09bb499',
        'type'=>'article',
        'headline'=>'[DEMO] Public-safety reporting preview',
        'deck'=>'Synthetic test content only. Not real reporting.',
        'display_time'=>'DEMO · synthetic',
        'author'=>'PMNow DEMO desk',
        'status'=>'published',
        'media_ids'=>[],
        'category_ids'=>[],
        'location_ids'=>[],
        'demo_fixture_suite'=>'pmnow-safe-demo-v1',
    ];
    $sample=$stories->hydrate($data);
    $assert(($sample['is_sample']??false)===true,'tagged synthetic content detected');
    $assert(($sample['headline']??'')==='Public-safety reporting preview','title prefix removed for display');
    $assert(($sample['display_time']??'')==='Illustrative only','old DEMO date label hidden');
    $assert(strpos((string)$sample['author'],'DEMO')===false,'presentation-only source label cleaned');

    $normal=$stories->hydrate(array_replace($data,[
        'headline'=>'Verified source-based civic reporting',
        'demo_fixture_suite'=>null,
        'display_time'=>'10:30',
        'author'=>'City reporter',
    ]));
    $assert(($normal['is_sample']??true)===false,'normal reporting never gets sample seal');
    $assert(($normal['headline']??'')==='Verified source-based civic reporting','normal story unchanged');

    $legacy=$stories->hydrate(array_replace($data,['demo_fixture_suite'=>null]));
    $assert(($legacy['is_sample']??false)===true,'older sample headline marker retained as provenance');
    $assert(($legacy['headline']??'')==='Public-safety reporting preview','older sample headline cleaned');

    $seo=(new SeoService($app['config']))->story($sample);
    $assert(($seo['robots']??'')==='noindex,nofollow','synthetic content is noindex');
    $assert(($seo['json_ld']??true)===null,'synthetic content is not published as NewsArticle schema');
    $realSeo=(new SeoService($app['config']))->story($normal);
    $assert(($realSeo['json_ld']['@type']??'')==='NewsArticle','normal journalism retains NewsArticle schema');

    $viewRoot=$app['root'].'/resources/views';
    ob_start();
    require $viewRoot.'/components/sample-badge.php';
    $html=(string)ob_get_clean();
    $assert(str_contains($html,'Sample content, not verified news'),'accessible label is explicit');
    $assert(str_contains($html,'/media/sample-content-badge.svg'),'vector badge uses the shared asset');
    $assert(is_file($app['root'].'/public/media/sample-content-badge.svg'),'badge vector exists');

    echo "Sample seal, existing fixtures, accessibility and SEO smoke: PASS\n";
} finally {
    $walker=new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach($walker as $item){
        if($item->isDir())rmdir($item->getPathname());
        else unlink($item->getPathname());
    }
    rmdir($root);
}
