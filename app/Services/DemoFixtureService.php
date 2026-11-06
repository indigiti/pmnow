<?php
declare(strict_types=1);

namespace PuneMirror\Services;

use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;
use PuneMirror\Repositories\File\FileStoryRepository;

/**
 * Shared synthetic-fixture engine for the web admin and the CLI.
 * Production is always blocked. Web mode additionally requires explicit opt-in.
 */
final class DemoFixtureService
{
    public const SUITE = 'pmnow-safe-demo-v1';
    private const COLLECTIONS = [
        'notifications','bookmarks','follows','utility-follows','live-updates',
        'story-updates','live-stories','utility-updates','utility-entities','contents',
        'jobs','stories','media','users','sources','locations','categories',
    ];

    public function __construct(
        private readonly string $root,
        private readonly array $config,
    ) {}

    public function webEnabled(): bool
    {
        $env = strtolower((string)($this->config['env'] ?? 'production'));
        return in_array($env, ['local','testing','staging'], true)
            && filter_var(pm_env('PMNOW_DEMO_DATA_ALLOWED', 'false'), FILTER_VALIDATE_BOOL);
    }

    public function status(): array
    {
        $counts = [];
        $store = new JsonStore($this->root . '/storage/data');
        foreach (self::COLLECTIONS as $collection) {
            $count = 0;
            foreach ($store->all($collection) as $record) {
                if (($record['demo_fixture_suite'] ?? '') === self::SUITE) $count++;
            }
            if ($count > 0) $counts[$collection] = $count;
        }
        return [
            'enabled'=>$this->webEnabled(),
            'environment'=>(string)($this->config['env'] ?? 'unknown'),
            'suite'=>self::SUITE,
            'counts'=>$counts,
            'total'=>array_sum($counts),
        ];
    }

    public function execute(string $operation, bool $viaWeb = false): array
    {
        if (!in_array($operation, ['apply','purge'], true)) {
            throw new \InvalidArgumentException('Unsupported demo operation');
        }
        $env = strtolower((string)($this->config['env'] ?? 'production'));
        if (!in_array($env, ['local','testing','staging'], true)) {
            throw new \RuntimeException('Demo data cannot run in production');
        }
        if ($env === 'staging' && !filter_var(pm_env('PMNOW_DEMO_DATA_ALLOWED', 'false'), FILTER_VALIDATE_BOOL)) {
            throw new \RuntimeException('Staging demo data requires explicit opt-in');
        }
        if ($viaWeb && !$this->webEnabled()) {
            throw new \RuntimeException('Web demo controls are disabled');
        }
        $lockPath = $this->root . '/storage/.demo-fixtures.lock';
        $lock = fopen($lockPath, 'c+');
        if (!$lock) throw new \RuntimeException('Cannot lock demo fixture storage');
        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                throw new \RuntimeException('Another demo operation is already in progress');
            }
            return $this->operate($operation);
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }
    }

    private function operate(string $operation): array
    {
        $root = $this->root;
        $env = strtolower((string)($this->config['env'] ?? 'production'));
        $purge = $operation === 'purge';
        $store = new JsonStore($root . '/storage/data');
        $storyRepo = new FileStoryRepository($store, new IndexManager($root . '/storage/indexes'));
        
        if ($purge) {
            $removed = [];
            // Delete ONLY rows created by this fixture suite. Never delete ordinary application records.
            foreach ([
                'notifications','bookmarks','follows','utility-follows','live-updates',
                'story-updates','live-stories','utility-updates','utility-entities','contents',
                'jobs','stories','media','users','sources','locations','categories'
            ] as $collection) {
                foreach ($store->all($collection) as $record) {
                    if (($record['demo_fixture_suite'] ?? '') !== self::SUITE) continue;
                    if ($store->delete($collection, (string)$record['id'])) {
                        $removed[$collection] = ($removed[$collection] ?? 0) + 1;
                    }
                }
            }
            $storyRepo->rebuildIndexes();
            return ['fixture_suite'=>self::SUITE,'operation'=>'purge','removed'=>$removed];
        }
        
        $cache = [];
        $inserted = [];
        $reused = [];
        $make = function(string $collection, string $key, array $fields) use ($store, &$cache, &$inserted, &$reused): array {
            if (!isset($cache[$collection])) {
                $cache[$collection] = [];
                foreach ($store->all($collection) as $old) {
                    if (($old['demo_fixture_suite'] ?? '') === self::SUITE && !empty($old['demo_fixture_key'])) {
                        $cache[$collection][(string)$old['demo_fixture_key']] = $old;
                    }
                }
            }
            if (isset($cache[$collection][$key])) {
                $reused[$collection] = ($reused[$collection] ?? 0) + 1;
                return $cache[$collection][$key];
            }
            $fields['demo_fixture_suite'] = self::SUITE;
            $fields['demo_fixture_key'] = $key;
            $fields['metadata'] = array_merge((array)($fields['metadata'] ?? []), [
                'demo'=>true, 'fixture_suite'=>self::SUITE,
            ]);
            $saved = $store->put($collection, $fields);
            $cache[$collection][$key] = $saved;
            $inserted[$collection] = ($inserted[$collection] ?? 0) + 1;
            return $saved;
        };
        
        $ensureTaxonomy = function(string $collection, string $slug, string $name) use ($store, $make): array {
            foreach ($store->all($collection) as $row) {
                if (($row['slug'] ?? '') === $slug) return $row;
            }
            return $make($collection, $slug, ['slug'=>$slug,'name'=>$name]);
        };
        
        $categories = [];
        foreach ([
            'traffic'=>'Traffic','metro'=>'Metro','civic'=>'Civic','weather'=>'Weather',
            'crime'=>'Crime','education'=>'Education','health'=>'Health','business'=>'Business',
            'sports'=>'Sports','lifestyle'=>'Lifestyle','events'=>'Events'
        ] as $slug=>$name) {
            $categories[$slug] = $ensureTaxonomy('categories', $slug, $name);
        }
        $locations = [];
        foreach ([
            'pune'=>'Pune','baner'=>'Baner','aundh'=>'Aundh','shivajinagar'=>'Shivajinagar',
            'koregaon-park'=>'Koregaon Park','kothrud'=>'Kothrud','hadapsar'=>'Hadapsar',
            'viman-nagar'=>'Viman Nagar','wakad'=>'Wakad','katraj'=>'Katraj',
            'pimpri'=>'Pimpri','hinjewadi'=>'Hinjewadi'
        ] as $slug=>$name) {
            $locations[$slug] = $ensureTaxonomy('locations', $slug, $name);
        }
        $source = $make('sources','demo-source',[
            'name'=>'PMNow DEMO — Synthetic Test Desk','provider'=>'manual','handle'=>'@pmnow-demo',
            'enabled'=>false,'sync_enabled'=>false,'sync_interval'=>3600,'classification_mode'=>'review',
            'health_status'=>'disabled','settings'=>[],'credential_env'=>[],
        ]);
        
        $images = [];
        foreach ([
            'rain'=>'home_rain.jpg','heritage'=>'home_fort.jpg','metro'=>'develop_metro.jpg',
            'road'=>'live_road.jpg','bus'=>'live_bus.jpg','food'=>'topic_food.jpg',
            'sports'=>'topic_sports.jpg','crime'=>'topic_crime.jpg','civic'=>'topic_civic.jpg',
            'event'=>'topic_events.jpg','kp'=>'reel_kp.jpg','river'=>'gallery_river.jpg',
            'baner'=>'explore_baner.jpg','aundh'=>'explore_aundh.jpg','kothrud'=>'explore_kothrud.jpg'
        ] as $key=>$filename) {
            $url = '/media/' . $filename;
            $existing = null;
            foreach ($store->all('media') as $item) {
                if (($item['url'] ?? '') === $url) { $existing = $item; break; }
            }
            $images[$key] = $existing ?? $make('media','media-'.$key,[
                'type'=>'image','url'=>$url,'caption'=>'DEMO placeholder image',
                'credit'=>'PMNow synthetic test dataset','position'=>1,
            ]);
        }
        
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata'));
        $at = static fn(int $minutesAgo): string => $now->modify('-'.$minutesAgo.' minutes')->format(DATE_ATOM);
        
        $stories = [];
        // Scenario: key, type, title, category, area, image, minutes old
        $scenarios = [
            ['rain-traffic','article','Rain and traffic bulletin layout test','traffic','pune','rain',5],
            ['metro-service','article','Metro commuter information card test','metro','shivajinagar','metro',12],
            ['ward-repair','article','Ward-level footpath update layout test','civic','aundh','civic',24],
            ['weather-panel','article','Weather conditions explainer sample','weather','baner','rain',35],
            ['crime-format','article','Public-safety reporting template preview','crime','kothrud','crime',45],
            ['school-calendar','article','School calendar information test','education','hadapsar','heritage',58],
            ['clinic-explainer','article','Public health information module test','health','viman-nagar','civic',70],
            ['market-watch','article','Neighbourhood business update preview','business','hinjewadi','baner',82],
            ['sports-weekend','article','Local sports coverage card test','sports','wakad','sports',97],
            ['food-guide','article','Neighbourhood food guide mock article','lifestyle','koregaon-park','food',115],
            ['event-guide','article','Community event listing preview','events','pimpri','event',140],
            ['commute-tips','article','PCMC commuting guide preview','traffic','pimpri','road',155],
            ['gallery-river','gallery','Riverfront photo gallery interaction test','civic','pune','river',165],
            ['developing-road','developing','Roadworks timeline interaction test','traffic','shivajinagar','road',9],
            ['live-metro','live','Metro live-update simulation','metro','pune','metro',3],
            ['watch-local','reel','Vertical news-card interaction test','lifestyle','baner','kp',17],
            ['watch-sport','reel','Sports short-form interaction test','sports','wakad','sports',29],
            ['watch-transit','reel','Transit short-form interaction test','metro','pune','bus',38],
        ];
        foreach ($scenarios as [$key,$type,$headline,$category,$area,$image,$minutes]) {
            $imageIds = [$images[$image]['id']];
            if ($type === 'gallery') {
                $imageIds = [$images['river']['id'],$images['baner']['id'],$images['aundh']['id'],$images['kothrud']['id']];
            }
            $stories[$key] = $make('stories','story-'.$key,[
                'status'=>'published','type'=>$type,'primary_content_id'=>null,
                'source_id'=>$source['id'],'author'=>'PMNow DEMO — Synthetic Test Desk',
                'headline'=>'[DEMO] '.$headline,
                'deck'=>'Synthetic test content only. This is not a real Pune Mirror report or live advisory.',
                'body'=>"DEMO DATA ONLY — This article is entirely fictional and provided to test PMNow layouts and workflows.\n\n"
                  ."Paragraph two tests the typography, content width, mobile reading rhythm, and readability of longer reporting.\n\n"
                  ."Paragraph three tests scrolling, sharing, bookmarks, feed rankings, and story navigation. "
                  ."No event or claim in this sample should be understood as a real-world report.",
                'media_ids'=>$imageIds,'category_ids'=>[$categories[$category]['id']],
                'location_ids'=>[$locations[$area]['id']],
                'tags'=>['demo','synthetic','qa-'.$category],
                'published_at'=>$at($minutes),'display_time'=>'DEMO · synthetic',
                'likes'=>0,'comments'=>0,
            ]);
        }
        
        foreach ([
            [0,'Initial synthetic update','A sample timeline item for newsroom presentation.'],
            [1,'Simulated status change','A fictional follow-up for chronology and refresh testing.'],
            [2,'Simulated resolution','A fictional final update; no real roads are affected.'],
        ] as [$i,$headline,$body]) {
            $make('story-updates','developing-'.$i,[
                'story_id'=>$stories['developing-road']['id'],
                'headline'=>'[DEMO] '.$headline,'body'=>$body,
                'published_at'=>$at(8-$i),
            ]);
        }
        $live = $make('live-stories','demo-live',[
            'story_id'=>$stories['live-metro']['id'],
            'status'=>'live','started_at'=>$at(12),'ended_at'=>null,
        ]);
        foreach ([
            [0,'metro','Simulation started'],
            [1,'traffic','Simulated transport advisory'],
            [2,'civic','Sample advisory revised'],
            [3,'metro','Simulated service normal'],
        ] as [$i,$type,$headline]) {
            $make('live-updates','live-update-'.$i,[
                'live_story_id'=>$live['id'],'type'=>$type,
                'headline'=>'[DEMO] '.$headline,
                'body'=>'Fictional update for testing timeline rendering and refresh. Not a real advisory.',
                'published_at'=>$at(11-$i),
            ]);
        }
        
        foreach ([
            ['review','review_required','Traffic advisory awaiting verification','traffic','shivajinagar'],
            ['ready','ready','Neighbourhood civic story ready for approval','civic','baner'],
            ['duplicate','duplicate','Potential duplicate for clustering tests','metro','pune'],
            ['rejected','rejected','Rejected sample with invalid reporting','crime','kothrud'],
            ['archived','archived','Archived event calendar sample','events','pune'],
            ['hindi','review_required','हिंदी शीर्षक प्रदर्शन परीक्षण','education','hadapsar'],
            ['marathi','review_required','मराठी बातमी मांडणी चाचणी','civic','pune'],
        ] as [$key,$status,$title,$category,$area]) {
            $make('contents','editorial-'.$key,[
                'source_id'=>$source['id'],'external_id'=>'pmnow-demo-'.$key,
                'provider'=>'manual','source_handle'=>'@pmnow-demo','content_type'=>'article',
                'title'=>'[DEMO] '.$title,
                'body'=>'Synthetic unpublished newsroom content, for editorial testing only.',
                'caption'=>'DEMO — Not a verified news item.',
                'workflow_status'=>$status,'language'=>$key==='hindi'?'hi':($key==='marathi'?'mr':'en'),
                'final_category_slugs'=>[$category],'final_location_slugs'=>[$area],
                'entity_names'=>[],'media_ids'=>[],
                'published_at'=>$at(15),'discovered_at'=>$at(14),'modified_at'=>$at(13),
            ]);
        }
        
        $utilityEntities = [];
        foreach ([
            ['traffic','Synthetic road corridor','demo-road','shivajinagar'],
            ['transit','Synthetic transit status','demo-transit','pune'],
            ['weather','Synthetic weather bulletin','demo-weather','pune'],
            ['air','Synthetic air quality reading','demo-air','baner'],
            ['civic','Synthetic civic works notice','demo-civic','aundh'],
            ['outage','Synthetic planned outage','demo-outage','hadapsar'],
            ['emergency','Synthetic emergency drill','demo-emergency','pune'],
            ['event','Synthetic community event','demo-event','koregaon-park'],
        ] as [$kind,$name,$slug,$area]) {
            $entity = $make('utility-entities','utility-entity-'.$kind,[
                'kind'=>$kind,'name'=>'[DEMO] '.$name,'slug'=>$slug,
                'authority'=>'PMNow synthetic QA','areas'=>[$locations[$area]['name']],
                'status'=>'active','source_label'=>'DEMO · No real authority',
                'source_url'=>null,
            ]);
            $utilityEntities[$kind] = $entity;
            $make('utility-updates','utility-update-'.$kind,[
                'entity_id'=>$entity['id'],
                'title'=>'[DEMO] '.ucfirst($kind).' status scenario',
                'summary'=>'Fictional advisory for interface and alert testing. Not real-time information.',
                'severity'=>$kind==='emergency'?'critical':($kind==='outage'?'major':'info'),
                'status'=>'active','area'=>$locations[$area]['name'],
                'published_at'=>$at(2),'verified_at'=>null,
                'source_label'=>'DEMO · Synthetic and unverified','source_url'=>null,
                'is_public'=>true,
            ]);
        }
        $make('utility-updates','utility-resolved-traffic',[
            'entity_id'=>$utilityEntities['traffic']['id'],
            'title'=>'[DEMO] Resolved road scenario','summary'=>'Previously active synthetic scenario, now resolved.',
            'severity'=>'advisory','status'=>'resolved','area'=>'Shivajinagar',
            'published_at'=>$at(90),'verified_at'=>null,'resolved_at'=>$at(30),
            'source_label'=>'DEMO · Synthetic and unverified','source_url'=>null,'is_public'=>true,
        ]);
        
        $banerReader = $make('users','reader-baner',[
            'name'=>'DEMO Reader · Baner','email'=>null,'role'=>'reader','status'=>'active',
            'preferences'=>['city'=>'Pune','areas'=>['Baner','Aundh'],'channels'=>['Traffic','Civic']],
            'notification_preferences'=>['enabled'=>true,'breaking'=>true,'following'=>true,'area_alerts'=>true,
                'topic_alerts'=>true,'morning_digest'=>true,'evening_digest'=>false,'weekend_digest'=>false,
                'timezone'=>'Asia/Kolkata'],
        ]);
        $pcmcReader = $make('users','reader-pcmc',[
            'name'=>'DEMO Reader · PCMC','email'=>null,'role'=>'reader','status'=>'active',
            'preferences'=>['city'=>'Pune','areas'=>['Pimpri','Wakad'],'channels'=>['Sports','Events']],
            'notification_preferences'=>['enabled'=>true,'breaking'=>false,'following'=>true,'area_alerts'=>true,
                'topic_alerts'=>true,'morning_digest'=>false,'evening_digest'=>true,'weekend_digest'=>false,
                'timezone'=>'Asia/Kolkata'],
        ]);
        $make('bookmarks','baner-bookmark',[
            'user_id'=>$banerReader['id'],'story_id'=>$stories['rain-traffic']['id'],
        ]);
        $make('follows','baner-story-follow',[
            'user_id'=>$banerReader['id'],'story_id'=>$stories['developing-road']['id'],'follow_type'=>'story',
        ]);
        $make('utility-follows','baner-utility-follow',[
            'user_id'=>$banerReader['id'],'entity_id'=>$utilityEntities['traffic']['id'],
        ]);
        $make('bookmarks','pcmc-bookmark',[
            'user_id'=>$pcmcReader['id'],'story_id'=>$stories['sports-weekend']['id'],
        ]);
        foreach ([
            ['baner-breaking',$banerReader['id'],'breaking_alert',$stories['rain-traffic']['id'],'Synthetic breaking alert',null],
            ['baner-utility',$banerReader['id'],'utility_update',null,'Synthetic utility alert',null],
            ['pcmc-digest',$pcmcReader['id'],'digest_evening',null,'Synthetic evening digest',$at(120)],
        ] as [$key,$userId,$type,$storyId,$title,$readAt]) {
            $make('notifications','notification-'.$key,[
                'user_id'=>$userId,'story_id'=>$storyId,'type'=>$type,
                'title'=>'[DEMO] '.$title,
                'body'=>'For PMNow notification UI testing only.','read_at'=>$readAt,
                'distribution_key'=>'fixture:'.self::SUITE.':'.$key,
            ]);
        }
        // Never enqueue runnable demo jobs. These are historical status examples only.
        foreach (['completed','failed'] as $state) {
            $make('jobs','job-'.$state,[
                'type'=>'DEMO_NON_EXECUTABLE','subject_id'=>null,'status'=>$state,
                'attempts'=>$state==='failed'?3:1,'max_attempts'=>3,
                'available_at'=>$at(60),'completed_at'=>$state==='completed'?$at(58):null,
                'last_error'=>$state==='failed'?'Synthetic worker failure for dashboard testing':null,
                'payload'=>['demo'=>true],
            ]);
        }
        
        $storyRepo->rebuildIndexes();
        return [
            'fixture_suite'=>self::SUITE,'operation'=>'apply','environment'=>$env,
            'inserted'=>$inserted,'already_present'=>$reused,
            'note'=>'Synthetic data only. Safe to rerun. No real news, alerts or providers were contacted.',
        ];
        
    }
}
