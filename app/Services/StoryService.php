<?php
namespace PuneMirror\Services;

use PuneMirror\Contracts\MediaRepository;
use PuneMirror\Contracts\StoryRepository;
use PuneMirror\Core\JsonStore;

final class StoryService
{
    public function __construct(
        private readonly StoryRepository $stories,
        private readonly MediaRepository $media,
        private readonly JsonStore $store
    ) {}

    public function hydrate(array $story): array
    {
        $story['media'] = $this->media->findMany($story['media_ids'] ?? []);
        $story['categories'] = $this->resolve('categories', $story['category_ids'] ?? []);
        $story['locations'] = $this->resolve('locations', $story['location_ids'] ?? []);
        $story['source'] = isset($story['source_id']) ? $this->store->get('sources', (string)$story['source_id']) : null;
        // Presentation-only: keep the original synthetic marker in storage while
        // replacing the headline prefix with a consistent accessible badge in views.
        // Never apply this to an ordinary published news story.
        $rawHeadline = (string)($story['headline'] ?? '');
        $sampleByPrefix = preg_match('/^\\s*\\[DEMO\\](?:\\s+|$)/iu', $rawHeadline) === 1;
        $story['is_sample'] = ($story['demo_fixture_suite'] ?? '') === 'pmnow-safe-demo-v1' || $sampleByPrefix;
        if ($story['is_sample']) {
            $story['headline'] = trim((string)(preg_replace('/^\\s*\\[DEMO\\]\\s*/iu', '', $rawHeadline) ?? $rawHeadline));
            // Existing fixture rows may still contain this wording. Keep data
            // immutable and normalize only reader-facing presentation fields.
            $story['headline'] = (string)(preg_replace('/\\bsample\\b/iu','test',$story['headline']) ?? $story['headline']);
            foreach (['deck','body'] as $field) {
                if (isset($story[$field]) && is_string($story[$field])) {
                    $story[$field] = (string)(preg_replace('/\\bsample\\b/iu','test',$story[$field]) ?? $story[$field]);
                }
            }
            if (preg_match('/\\bDEMO\\b/i', (string)($story['display_time'] ?? ''))) {
                $story['display_time'] = 'Illustrative only';
            }
            // This user-facing author/source should not repeat fixture labels.
            // Keep unmodified attribution in the stored test records.
            $story['author'] = 'PMNow Test Desk';
            if (is_array($story['source'])) {
                $story['source']['name'] = 'PMNow Test Desk';
            }
        }
        $story['cluster'] = isset($story['story_cluster_id']) && $story['story_cluster_id'] ? $this->store->get('story-clusters', (string)$story['story_cluster_id']) : null;
        if (($story['type'] ?? '') === 'developing') {
            $story['updates'] = $this->storyUpdates($story['id']);
        }
        $story['path']=pm_story_path($story);
        return $story;
    }

    public function find(string $id): ?array
    {
        $story = $this->stories->find($id);
        return $story ? $this->hydrate($story) : null;
    }

    public function feed(int $limit = 20): array
    {
        return $this->feedPage($limit)['rows'];
    }

    public function feedPage(int $limit = 20, ?string $cursor = null): array
    {
        $page=$this->stories->page($limit,null,$cursor);
        return [
            'rows'=>array_map(fn($s)=>$this->hydrate($s),$page['rows']??[]),
            'has_more'=>(bool)($page['has_more']??false),
            'next_cursor'=>$page['next_cursor']??null,
        ];
    }

    public function byType(string $type, int $limit = 20): array
    {
        return array_map(fn($s) => $this->hydrate($s), $this->stories->latest($limit, $type));
    }

    public function storyUpdates(string $storyId): array
    {
        $rows = array_values(array_filter($this->store->all('story-updates'), fn($r) => ($r['story_id'] ?? '') === $storyId));
        usort($rows, fn($a,$b) => strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));
        return array_map([$this,'cleanSyntheticUpdate'],$rows);
    }

    public function liveStoryByStory(string $storyId): ?array
    {
        foreach ($this->store->all('live-stories') as $live) {
            if (($live['story_id'] ?? '') === $storyId) {
                $live['updates'] = $this->liveUpdates($live['id']);
                return $live;
            }
        }
        return null;
    }

    public function liveUpdates(string $liveId, ?string $after = null): array
    {
        $rows = array_values(array_filter($this->store->all('live-updates'), function($r) use ($liveId, $after) {
            if (($r['live_story_id'] ?? '') !== $liveId) return false;
            if ($after && strcmp((string)($r['published_at'] ?? ''), $after) <= 0) return false;
            return true;
        }));
        usort($rows, fn($a,$b) => strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));
        return array_map([$this,'cleanSyntheticUpdate'],$rows);
    }

    public function taxonomyFeed(string $kind,string $slug,int $limit=30):array
    {
        $slug=strtolower(trim($slug));$out=[];
        foreach($this->stories->all() as $story){
            if(($story['status']??'')!=='published')continue;
            $hydrated=$this->hydrate($story);
            $rows=$kind==='area'?($hydrated['locations']??[]):($hydrated['categories']??[]);
            foreach($rows as $row){
                if(strtolower((string)($row['slug']??''))===$slug){$out[]=$hydrated;break;}
            }
            if(count($out)>=$limit)break;
        }
        return $out;
    }

    public function search(string $q): array
    {
        $q = function_exists('mb_strtolower') ? mb_strtolower(trim($q)) : strtolower(trim($q));
        if ($q === '') return [];
        $hits = [];
        foreach ($this->stories->all() as $story) {
            $rawHay = implode(' ', [$story['headline'] ?? '', $story['deck'] ?? '', $story['body'] ?? '', implode(' ', $story['tags'] ?? [])]);
            $hay = function_exists('mb_strtolower') ? mb_strtolower($rawHay) : strtolower($rawHay);
            if (str_contains($hay, $q)) $hits[] = $this->hydrate($story);
        }
        return $hits;
    }

    /**
     * Existing fixture timeline records can predate the UI refresh. Only
     * normalize their public presentation, never modify stored editorial data.
     */
    private function cleanSyntheticUpdate(array $row): array
    {
        $raw=(string)($row['headline']??$row['title']??'');
        $synthetic=($row['demo_fixture_suite']??'')==='pmnow-safe-demo-v1'
            || preg_match('/^\\s*\\[DEMO\\](?:\\s+|$)/iu',$raw)===1;
        if (!$synthetic) return $row;
        foreach(['headline','title','summary','body'] as $key){
            if (!isset($row[$key])||!is_string($row[$key])) continue;
            $value=(string)(preg_replace('/^\\s*\\[DEMO\\]\\s*/iu','',$row[$key])??$row[$key]);
            $row[$key]=(string)(preg_replace('/\\bsample\\b/iu','test',$value)??$value);
        }
        return $row;
    }

    private function resolve(string $collection, array $ids): array
    {
        $out=[]; foreach ($ids as $id) if ($row=$this->store->get($collection,(string)$id)) $out[]=$row; return $out;
    }
}
