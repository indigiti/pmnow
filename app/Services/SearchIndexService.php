<?php
namespace PuneMirror\Services;

use PuneMirror\Contracts\StoryRepository;
use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;

final class SearchIndexService
{
    public function __construct(
        private readonly StoryRepository $stories,
        private readonly JsonStore $store,
        private readonly IndexManager $indexes,
        private readonly StoryService $storyService
    ) {}

    public function rebuild(): array
    {
        $tokens = [];
        $docs = [];
        foreach ($this->stories->all() as $story) {
            if (($story['status'] ?? '') !== 'published') continue;
            $id = (string)$story['id'];
            $categories = $this->names('categories', $story['category_ids'] ?? []);
            $locations = $this->names('locations', $story['location_ids'] ?? []);
            $text = implode(' ', [
                $story['headline'] ?? '', $story['deck'] ?? '', $story['body'] ?? '',
                implode(' ', $story['tags'] ?? []), implode(' ', $categories), implode(' ', $locations)
            ]);
            $docTokens = $this->tokenize($text);
            $docs[$id] = ['headline'=>$story['headline'] ?? '', 'type'=>$story['type'] ?? 'article', 'categories'=>$categories, 'locations'=>$locations, 'published_at'=>$story['published_at'] ?? null];
            foreach ($docTokens as $token) $tokens[$token][$id] = ($tokens[$token][$id] ?? 0) + 1;
        }
        foreach ($tokens as $token => $weights) arsort($tokens[$token]);
        $payload = ['version'=>1,'generated_at'=>gmdate('c'),'tokens'=>$tokens,'docs'=>$docs];
        $this->indexes->write('search', $payload);
        return ['documents'=>count($docs),'tokens'=>count($tokens),'generated_at'=>$payload['generated_at']];
    }

    public function query(string $q, int $limit = 20): array
    {
        $index = $this->indexes->read('search');
        $latest = ''; foreach ($this->stories->all() as $story) $latest = max($latest, (string)($story['updated_at'] ?? $story['published_at'] ?? ''));
        if (!$index || ($latest !== '' && strcmp((string)($index['generated_at'] ?? ''), $latest) < 0)) { $this->rebuild(); $index = $this->indexes->read('search'); }
        $terms = $this->tokenize($q);
        if (!$terms) return [];
        $scores = [];
        foreach ($terms as $term) {
            foreach (($index['tokens'][$term] ?? []) as $id => $weight) $scores[$id] = ($scores[$id] ?? 0) + (int)$weight;
            foreach (($index['tokens'] ?? []) as $token => $weights) {
                if ($token !== $term && str_starts_with($token, $term) && strlen($term) >= 3) {
                    foreach ($weights as $id => $weight) $scores[$id] = ($scores[$id] ?? 0) + max(1, (int)$weight - 1);
                }
            }
        }
        arsort($scores);
        $out = [];
        foreach (array_slice(array_keys($scores), 0, $limit) as $id) {
            if ($story = $this->storyService->find((string)$id)) { $story['_search_score'] = $scores[$id]; $out[] = $story; }
        }
        return $out;
    }

    private function tokenize(string $text): array
    {
        $text = function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = array_flip(['the','and','for','with','from','this','that','are','was','were','has','have','into','across','pune']);
        return array_values(array_unique(array_filter($parts, function($x) use ($stop) { $len = function_exists('mb_strlen') ? mb_strlen($x) : strlen($x); return $len >= 2 && !isset($stop[$x]); })));
    }

    private function names(string $collection, array $ids): array
    {
        $out=[]; foreach ($ids as $id) if ($r=$this->store->get($collection,(string)$id)) $out[]=(string)($r['name'] ?? $r['slug'] ?? ''); return $out;
    }
}
