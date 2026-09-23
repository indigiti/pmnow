<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\ContentRepository;
use PuneMirror\Core\JsonStore;

final class FileContentRepository implements ContentRepository
{
    public function __construct(private readonly JsonStore $store) {}
    public function find(string $id): ?array { return $this->store->get('contents', $id); }
    public function save(array $content): array
    {
        $saved = $this->store->put('contents', $content);
        $this->store->appendEvent('contents', ['event' => 'content.saved', 'content_id' => $saved['id'], 'source_id' => $saved['source_id'] ?? null]);
        return $saved;
    }
    public function all(): array
    {
        $rows = $this->store->all('contents');
        usort($rows, fn($a,$b) => strcmp((string)($b['published_at'] ?? $b['created_at'] ?? ''), (string)($a['published_at'] ?? $a['created_at'] ?? '')));
        return $rows;
    }
    public function findByExternal(string $sourceId, string $externalId): ?array
    {
        foreach ($this->all() as $row) {
            if (($row['source_id'] ?? '') === $sourceId && ($row['external_id'] ?? '') === $externalId) return $row;
        }
        return null;
    }
}
