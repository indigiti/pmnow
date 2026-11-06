<?php
declare(strict_types=1);

namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;

/**
 * Maps location-tagged published reporting to indicative Pune area centroids.
 * Never represents the resulting count as measured traffic speed or live congestion.
 */
final class AreaPulseService
{
    // Approximate neighbourhood centroids for area discovery, not street navigation.
    private const AREAS = [
        'pune'         => ['Pune',18.5204,73.8567],
        'baner'        => ['Baner',18.5590,73.7898],
        'aundh'        => ['Aundh',18.5571,73.8077],
        'shivajinagar' => ['Shivajinagar',18.5314,73.8446],
        'koregaon-park'=> ['Koregaon Park',18.5362,73.8933],
        'kothrud'      => ['Kothrud',18.5074,73.8077],
        'hadapsar'     => ['Hadapsar',18.5089,73.9260],
        'viman-nagar'  => ['Viman Nagar',18.5679,73.9143],
        'wakad'        => ['Wakad',18.5975,73.7635],
        'katraj'       => ['Katraj',18.4529,73.8619],
        'pimpri'       => ['Pimpri',18.6298,73.7997],
        'hinjewadi'    => ['Hinjewadi',18.5943,73.7374],
    ];

    public function __construct(private readonly JsonStore $store) {}

    public function areas(array $selected = [], int $days = 7): array
    {
        $days = max(1,min(30,$days));
        $cutoff = time() - $days * 86400;
        $locationToSlug = [];
        foreach ($this->store->all('locations') as $location) {
            $slug = strtolower((string)($location['slug']??''));
            if(isset(self::AREAS[$slug])) $locationToSlug[(string)$location['id']]=$slug;
        }
        $trafficCategories = [];
        foreach ($this->store->all('categories') as $category) {
            if (($category['slug']??'')==='traffic') $trafficCategories[(string)$category['id']]=true;
        }
        $seen = [];
        $counts = [];
        foreach (self::AREAS as $slug=>$entry) {
            $counts[$slug]=['stories'=>0,'traffic_reports'=>0,'last_reported_at'=>null,
                'latest_headline'=>null,'latest_path'=>null,'latest_is_sample'=>false,'demo_reports'=>0];
            $seen[$slug]=[];
        }
        foreach ($this->store->all('stories') as $story) {
            if (($story['status']??'')!=='published') continue;
            $published = strtotime((string)($story['published_at']??''));
            if ($published === false || $published<$cutoff || $published>time()+3600) continue;
            $traffic = count(array_intersect(array_map('strval',(array)($story['category_ids']??[])),array_keys($trafficCategories)))>0;
            foreach ((array)($story['location_ids']??[]) as $locationId) {
                $slug=$locationToSlug[(string)$locationId]??null;
                if ($slug===null || isset($seen[$slug][(string)$story['id']])) continue;
                $seen[$slug][(string)$story['id']]=true;
                $counts[$slug]['stories']++;
                if ($traffic) $counts[$slug]['traffic_reports']++;
                if (($story['demo_fixture_suite']??'')==='pmnow-safe-demo-v1') $counts[$slug]['demo_reports']++;
                if ($counts[$slug]['last_reported_at']===null || $published > strtotime((string)$counts[$slug]['last_reported_at'])) {
                    $counts[$slug]['last_reported_at']=$story['published_at'];
                    $headline=(string)($story['headline']??'');
                    $counts[$slug]['latest_is_sample'] =
                        ($story['demo_fixture_suite']??'')==='pmnow-safe-demo-v1'
                        || preg_match('/^\\s*\\[DEMO\\](?:\\s+|$)/iu',$headline)===1;
                    $cleanHeadline=trim((string)(preg_replace('/^\\s*\\[DEMO\\]\\s*/iu','',$headline)??$headline));
                    if($counts[$slug]['latest_is_sample']){
                        $cleanHeadline=(string)(preg_replace('/\\bsample\\b/iu','test',$cleanHeadline)??$cleanHeadline);
                    }
                    $counts[$slug]['latest_headline']=$cleanHeadline;
                    $counts[$slug]['latest_path']=pm_story_path($story);
                }
            }
        }
        $selected = array_map('strtolower',array_map('trim',$selected));
        $out = [];
        foreach (self::AREAS as $slug=>[$name,$lat,$lon]) {
            $item=$counts[$slug];
            $item['slug']=$slug;
            $item['name']=$name;
            $item['lat']=$lat;
            $item['lon']=$lon;
            $item['x']=round(max(5,min(95,($lon-73.71)/(73.95-73.71)*100)),1);
            $item['y']=round(max(5,min(95,(18.67-$lat)/(18.67-18.43)*100)),1);
            $item['following']=in_array(strtolower($name),$selected,true);
            $out[]=$item;
        }
        usort($out,static fn(array $a,array $b) =>
            ($b['following'] <=> $a['following'])
            ?: ($b['traffic_reports'] <=> $a['traffic_reports'])
            ?: ($b['stories'] <=> $a['stories'])
            ?: strcmp($a['name'],$b['name']));
        return $out;
    }

    /**
     * Small static directory for consent-based neighbourhood suggestions.
     * No story JSON scan when displaying the settings form.
     */
    public function centers():array
    {
        $rows=[];
        foreach (self::AREAS as $slug=>[$name,$lat,$lon]) {
            $rows[]=['slug'=>$slug,'name'=>$name,'lat'=>$lat,'lon'=>$lon];
        }
        return $rows;
    }

    public function totals(array $areas): array
    {
        return [
            'areas'=>count($areas),
            'with_reports'=>count(array_filter($areas,static fn($row)=>$row['stories']>0)),
            'traffic_reports'=>array_sum(array_column($areas,'traffic_reports')),
            'articles'=>array_sum(array_column($areas,'stories')),
        ];
    }
}
