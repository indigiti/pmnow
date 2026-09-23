<?php
namespace PuneMirror\Services;

use PuneMirror\Contracts\AnalysisRepository;
use PuneMirror\Contracts\ClusterRepository;
use PuneMirror\Contracts\ContentRepository;
use PuneMirror\Contracts\MediaRepository;
use PuneMirror\Contracts\StoryRepository;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\PythonClient;
use PuneMirror\Core\UuidV7;
use PuneMirror\Core\UrlGuard;
use RuntimeException;

final class ContentHubService
{
    public function __construct(
        private readonly ContentRepository $contents,
        private readonly AnalysisRepository $analysis,
        private readonly ClusterRepository $clusters,
        private readonly MediaRepository $media,
        private readonly StoryRepository $stories,
        private readonly SourceService $sources,
        private readonly JsonStore $store,
        private readonly PythonClient $python,
        private readonly array $config
    ) {}

    public function allContent(): array
    {
        return array_map(function(array $row): array {
            $row['analysis'] = $this->analysis->forContent((string)$row['id']);
            $row['cluster'] = $this->clusters->containing((string)$row['id']);
            return $row;
        }, $this->contents->all());
    }

    public function clusters(): array { return $this->clusters->all(); }

    public function manualImport(array $raw, ?string $sourceId = null, bool $publish = true): array
    {
        $source = $sourceId ? $this->sources->find($sourceId) : $this->firstProviderSource('manual');
        if (!$source) throw new RuntimeException('Manual source is not configured');
        $response = $this->python->post('/v1/content/normalize', [
            'source' => $this->sources->forEngine($source),
            'raw' => $raw,
        ]);
        $normalized = $response['data'] ?? null;
        if (!is_array($normalized)) throw new RuntimeException('Normalization failed');
        return $this->ingestNormalized($normalized, $publish);
    }

    public function syncSource(string $sourceId, int $limit = 10, ?bool $publish = null): array
    {
        $source = $this->sources->find($sourceId);
        if (!$source) throw new RuntimeException('Source not found');
        if (!($source['enabled'] ?? true)) throw new RuntimeException('Source is disabled');
        try {
            $response = $this->sources->syncPayload($sourceId, $limit, $source['last_cursor'] ?? null);
            $items = $response['data'] ?? [];
            $meta = $response['meta'] ?? [];
            $doPublish = $publish ?? (($source['classification_mode'] ?? 'review') === 'auto');
            $results = [];
            foreach ($items as $item) {
                if (is_array($item)) $results[] = $this->ingestNormalized($item, $doPublish);
            }
            $this->sources->markSync($sourceId, isset($meta['cursor']) ? (string)$meta['cursor'] : null);
            return ['source_id' => $sourceId, 'count' => count($results), 'items' => $results, 'meta' => $meta];
        } catch (\Throwable $e) {
            $this->sources->markSync($sourceId, $source['last_cursor'] ?? null, $e->getMessage());
            throw $e;
        }
    }

    public function analyzeExisting(string $contentId): array
    {
        $content = $this->contents->find($contentId);
        if (!$content) throw new RuntimeException('Content not found');
        return $this->runIntelligence($content);
    }


    public function publishExisting(string $contentId): array
    {
        $content = $this->contents->find($contentId);
        if (!$content) throw new RuntimeException('Content not found');
        $intelligence = $this->runIntelligence($content);
        $content = $this->applyIntelligence($content, $intelligence);
        $content['workflow_status'] = 'published';
        $content = $this->contents->save($content);
        $matches = $this->runRelations($content);
        $cluster = $this->clusterContent($content, $matches, $intelligence);
        $story = $this->publishStory($content, $intelligence, $cluster);
        $content['story_id'] = $story['id'];
        $content = $this->contents->save($content);
        return [
            'content' => $content,
            'intelligence' => $intelligence,
            'duplicates' => $matches['duplicates'],
            'related' => $matches['related'],
            'cluster' => $cluster,
            'story' => $story,
        ];
    }

    public function ingestNormalized(array $normalized, bool $publish = false): array
    {
        $sourceId = (string)($normalized['source_id'] ?? '');
        $externalId = trim((string)($normalized['external_id'] ?? ''));
        if ($sourceId === '' || $externalId === '') throw new RuntimeException('Normalized content requires source_id and external_id');

        $existing = $this->contents->findByExternal($sourceId, $externalId);
        $record = $existing ?: ['id' => UuidV7::generate()];
        foreach ($normalized as $key => $value) $record[$key] = $value;
        $record['workflow_status'] = $publish ? 'published' : ($record['workflow_status'] ?? 'ready');
        $record['media_ids'] = $existing['media_ids'] ?? $this->persistMedia($normalized['media'] ?? [], $sourceId);
        unset($record['media']);
        $content = $this->contents->save($record);

        $intelligence = $this->runIntelligence($content);
        $content = $this->applyIntelligence($content, $intelligence);
        $content = $this->contents->save($content);
        $matches = $this->runRelations($content);
        $cluster = $this->clusterContent($content, $matches, $intelligence);
        $story = $publish ? $this->publishStory($content, $intelligence, $cluster) : null;
        if ($story) {
            $content['story_id'] = $story['id'];
            $content = $this->contents->save($content);
        }
        return [
            'content' => $content,
            'intelligence' => $intelligence,
            'duplicates' => $matches['duplicates'],
            'related' => $matches['related'],
            'cluster' => $cluster,
            'story' => $story,
        ];
    }

    private function runIntelligence(array $content): array
    {
        $payload = [
            'content' => $content,
            'taxonomy' => $this->store->all('categories'),
            'locations' => $this->store->all('locations'),
            'rules' => $this->store->all('rules'),
            'allow_external_ai' => (bool)($this->config['intelligence']['external_ai'] ?? false),
        ];
        $response = $this->python->post('/v1/content/analyze', $payload);
        $result = $response['data'] ?? [];
        if (!is_array($result)) throw new RuntimeException('Invalid intelligence response');
        $this->persistAnalysisStages((string)$content['id'], $result);
        return $result;
    }

    private function persistAnalysisStages(string $contentId, array $result): void
    {
        $version = (string)($result['analyzer'] ?? 'intelligence-beta-v1');
        foreach (['language','category','locations','entities','summary','external_ai'] as $type) {
            if (!array_key_exists($type, $result) || $result[$type] === null) continue;
            $stage = $result[$type];
            $confidence = null;
            if (is_array($stage) && isset($stage['confidence'])) $confidence = (float)$stage['confidence'];
            if ($type === 'locations' && is_array($stage) && isset($stage[0]['confidence'])) $confidence = (float)$stage[0]['confidence'];
            $this->analysis->save([
                'id' => UuidV7::generate(),
                'content_id' => $contentId,
                'analyzer' => 'content-engine',
                'analyzer_version' => $version,
                'analysis_type' => $type,
                'result_json' => $stage,
                'confidence' => $confidence,
            ]);
        }
    }

    private function applyIntelligence(array $content, array $result): array
    {
        $slug = (string)($result['category']['category_slug'] ?? '');
        $content['ai_category_slug'] = $slug ?: null;
        $content['ai_category_confidence'] = $result['category']['confidence'] ?? null;
        $content['decision_source'] = $result['category']['decision_source'] ?? null;
        $content['language'] = $result['language']['language'] ?? ($content['language'] ?? 'und');
        $content['summary'] = $result['summary']['summary'] ?? '';
        $content['category_slugs'] = $slug ? [$slug] : [];
        $content['location_slugs'] = array_values(array_filter(array_map(fn($x) => $x['slug'] ?? null, $result['locations'] ?? [])));
        $content['entity_names'] = array_values(array_filter(array_map(fn($x) => $x['name'] ?? null, $result['entities'] ?? [])));
        $content['final_category_slugs'] ??= $content['category_slugs'];
        $content['final_location_slugs'] ??= $content['location_slugs'];
        return $content;
    }

    private function runRelations(array $content): array
    {
        $candidates = array_values(array_filter($this->contents->all(), fn($row) => ($row['id'] ?? '') !== ($content['id'] ?? '')));
        $compact = array_slice($candidates, 0, 250);
        $duplicates = ($this->python->post('/v1/content/duplicate', ['content' => $content, 'candidates' => $compact, 'limit' => 8])['data'] ?? []);
        $related = ($this->python->post('/v1/content/related', ['content' => $content, 'candidates' => $compact, 'limit' => 8])['data'] ?? []);
        $this->analysis->save([
            'id' => UuidV7::generate(), 'content_id' => $content['id'], 'analyzer' => 'content-engine',
            'analyzer_version' => 'duplicate-beta-v1', 'analysis_type' => 'duplicate', 'result_json' => $duplicates,
            'confidence' => isset($duplicates[0]['score']) ? (float)$duplicates[0]['score'] : 0.0,
        ]);
        $this->analysis->save([
            'id' => UuidV7::generate(), 'content_id' => $content['id'], 'analyzer' => 'content-engine',
            'analyzer_version' => 'related-beta-v1', 'analysis_type' => 'related', 'result_json' => $related,
            'confidence' => isset($related[0]['score']) ? (float)$related[0]['score'] : 0.0,
        ]);
        return ['duplicates' => is_array($duplicates) ? $duplicates : [], 'related' => is_array($related) ? $related : []];
    }

    private function clusterContent(array $content, array $matches, array $intelligence): array
    {
        $anchor = null;
        foreach (array_merge($matches['duplicates'] ?? [], $matches['related'] ?? []) as $match) {
            if ((float)($match['score'] ?? 0) >= 0.60) { $anchor = $match; break; }
        }
        $cluster = $anchor ? $this->clusters->containing((string)$anchor['content_id']) : null;
        if (!$cluster) {
            $ids = [$content['id']];
            if ($anchor) $ids[] = $anchor['content_id'];
            $cluster = [
                'id' => UuidV7::generate(),
                'title' => $content['title'] ?: substr((string)($content['caption'] ?? $content['body'] ?? 'Story cluster'), 0, 140),
                'primary_content_id' => $anchor['content_id'] ?? $content['id'],
                'content_ids' => array_values(array_unique($ids)),
                'category_slug' => $intelligence['category']['category_slug'] ?? null,
                'location_slugs' => array_values(array_filter(array_map(fn($x) => $x['slug'] ?? null, $intelligence['locations'] ?? []))),
                'first_seen' => $content['published_at'] ?? $content['created_at'] ?? gmdate('c'),
                'last_updated' => gmdate('c'),
            ];
        } else {
            $cluster['content_ids'][] = $content['id'];
            $cluster['content_ids'] = array_values(array_unique($cluster['content_ids']));
            $cluster['last_updated'] = gmdate('c');
        }
        return $this->clusters->save($cluster);
    }

    private function publishStory(array $content, array $intelligence, array $cluster): array
    {
        $story = null;
        foreach ($this->stories->all() as $candidate) {
            if (($candidate['primary_content_id'] ?? '') === $content['id']) { $story = $candidate; break; }
        }
        $source = $this->sources->find((string)$content['source_id']);
        $headline = trim((string)($content['title'] ?? '')) ?: trim((string)($content['caption'] ?? ''));
        if ($headline === '') $headline = 'Pune Mirror update';
        $categoryIds = $this->idsForSlugs('categories', $content['final_category_slugs'] ?? []);
        $locationIds = $this->idsForSlugs('locations', $content['final_location_slugs'] ?? []);
        $type = match ((string)($content['content_type'] ?? 'article')) {
            'carousel' => 'gallery',
            'reel', 'video', 'short' => 'reel',
            default => 'article',
        };
        $record = ($story ?: ['id' => UuidV7::generate()]) + [];
        $record = array_merge($record, [
            'status' => 'published',
            'type' => $type,
            'primary_content_id' => $content['id'],
            'story_cluster_id' => $cluster['id'] ?? null,
            'source_id' => $content['source_id'],
            'author' => $source['name'] ?? ($content['source_handle'] ?? 'Pune Mirror'),
            'headline' => substr($headline, 0, 500),
            'deck' => $content['summary'] ?? ($content['caption'] ?? ''),
            'body' => $content['body'] ?: ($content['caption'] ?? ''),
            'media_ids' => $content['media_ids'] ?? [],
            'category_ids' => $categoryIds,
            'location_ids' => $locationIds,
            'tags' => $content['entity_names'] ?? [],
            'published_at' => $content['published_at'] ?? gmdate('c'),
            'display_time' => 'Latest',
        ]);
        return $this->stories->save($record);
    }

    private function persistMedia(array $items, string $sourceId): array
    {
        $ids = [];
        foreach ($items as $position => $item) {
            if (!is_array($item)) continue;
            $url = $item['cached_url'] ?? $item['source_url'] ?? $item['thumbnail'] ?? null;
            if (!$url || !UrlGuard::isSafeMediaUrl((string)$url)) continue;
            $saved = $this->media->save([
                'id' => UuidV7::generate(),
                'source_id' => $sourceId,
                'type' => $item['type'] ?? 'image',
                'url' => $url,
                'source_url' => $item['source_url'] ?? null,
                'cached_url' => $item['cached_url'] ?? null,
                'thumbnail' => $item['thumbnail'] ?? null,
                'width' => $item['width'] ?? null,
                'height' => $item['height'] ?? null,
                'duration' => $item['duration'] ?? null,
                'caption' => $item['caption'] ?? '',
                'credit' => $item['credit'] ?? ($item['provider'] ?? ''),
                'position' => $position + 1,
            ]);
            $ids[] = $saved['id'];
        }
        return $ids;
    }

    private function idsForSlugs(string $collection, array $slugs): array
    {
        $wanted = array_flip(array_map('strval', $slugs));
        $ids = [];
        foreach ($this->store->all($collection) as $row) {
            if (isset($wanted[(string)($row['slug'] ?? '')])) $ids[] = $row['id'];
        }
        return $ids;
    }

    private function firstProviderSource(string $provider): ?array
    {
        foreach ($this->sources->all() as $source) if (($source['provider'] ?? '') === $provider) return $source;
        return null;
    }
}
