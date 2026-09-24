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
        $story['cluster'] = isset($story['story_cluster_id']) && $story['story_cluster_id'] ? $this->store->get('story-clusters', (string)$story['story_cluster_id']) : null;
        if (($story['type'] ?? '') === 'developing') {
            $story['updates'] = $this->storyUpdates($story['id']);
        }
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
        return $rows;
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
        return $rows;
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

    private function resolve(string $collection, array $ids): array
    {
        $out=[]; foreach ($ids as $id) if ($row=$this->store->get($collection,(string)$id)) $out[]=$row; return $out;
    }
}
