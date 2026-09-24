<?php
namespace PuneMirror\Controllers;

use PuneMirror\Contracts\StoryRepository;
use PuneMirror\Core\JsonStore;
use PuneMirror\Services\StoryService;
use PuneMirror\Services\DistributionService;

final class DiscoveryController
{
    public function __construct(private readonly StoryRepository $stories,private readonly StoryService $storyService,private readonly JsonStore $store,private readonly ?DistributionService $distribution=null){}

    public function robots():never
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nAllow: /\nSitemap: ".pm_absolute_url('/sitemap.xml')."\nSitemap: ".pm_absolute_url('/news-sitemap.xml')."\n";
        exit;
    }

    public function sitemap():never
    {
        $urls=[
            ['loc'=>pm_absolute_url('/'),'lastmod'=>gmdate('c')],
            ['loc'=>pm_absolute_url('/explore'),'lastmod'=>gmdate('c')],
            ['loc'=>pm_absolute_url('/watch'),'lastmod'=>gmdate('c')],
            ['loc'=>pm_absolute_url('/utility'),'lastmod'=>gmdate('c')],
        ];
        foreach($this->store->all('categories') as $row)if(!empty($row['slug']))$urls[]=['loc'=>pm_absolute_url('/category/'.rawurlencode((string)$row['slug'])),'lastmod'=>(string)($row['updated_at']??gmdate('c'))];
        foreach($this->store->all('locations') as $row)if(!empty($row['slug']))$urls[]=['loc'=>pm_absolute_url('/area/'.rawurlencode((string)$row['slug'])),'lastmod'=>(string)($row['updated_at']??gmdate('c'))];
        foreach($this->stories->all() as $story){
            if(($story['status']??'')!=='published')continue;
            $urls[]=['loc'=>pm_absolute_url(pm_story_path($story)),'lastmod'=>(string)($story['updated_at']??$story['published_at']??gmdate('c'))];
        }
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach($urls as $u)echo '<url><loc>'.$this->x($u['loc']).'</loc><lastmod>'.$this->x($u['lastmod'])."</lastmod></url>\n";
        echo "</urlset>\n";exit;
    }

    public function newsSitemap():never
    {
        $cutoff=time()-172800;$rows=[];
        foreach($this->stories->all() as $story){
            if(($story['status']??'')!=='published')continue;
            $published=(string)($story['published_at']??'');$ts=strtotime($published);
            if(!$ts||$ts<$cutoff)continue;
            $rows[]=$story;
            if(count($rows)>=1000)break;
        }
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'."\n";
        foreach($rows as $story){
            echo '<url><loc>'.$this->x(pm_absolute_url(pm_story_path($story))).'</loc><news:news><news:publication><news:name>Pune Mirror</news:name><news:language>en</news:language></news:publication><news:publication_date>'.$this->x((string)$story['published_at']).'</news:publication_date><news:title>'.$this->x((string)($story['headline']??'Pune news'))."</news:title></news:news></url>\n";
        }
        echo "</urlset>\n";exit;
    }

    public function rss():never
    {
        $rows=$this->storyService->feed(50);
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<rss version="2.0"><channel><title>Pune Mirror Now</title><link>'.$this->x(pm_absolute_url('/')).'</link><description>Pune news, traffic, civic and neighbourhood updates.</description>';
        foreach($rows as $story){
            echo '<item><title>'.$this->x((string)$story['headline']).'</title><link>'.$this->x(pm_absolute_url(pm_story_path($story))).'</link><guid isPermaLink="true">'.$this->x(pm_absolute_url(pm_story_path($story))).'</guid><pubDate>'.gmdate(DATE_RSS,strtotime((string)($story['published_at']??'now'))).'</pubDate><description>'.$this->x((string)($story['deck']??'')).'</description></item>';
        }
        echo '</channel></rss>';exit;
    }

    public function channel(string $channel):never
    {
        if(!$this->distribution){http_response_code(503);header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>'distribution_unavailable']);exit;}
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=60');
        echo json_encode([
            'channel'=>$channel,
            'generated_at'=>gmdate('c'),
            'items'=>$this->distribution->channelFeed(20),
        ],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function x(string $value):string{return htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8');}
}
