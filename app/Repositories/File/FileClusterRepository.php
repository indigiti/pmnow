<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\ClusterRepository;
use PuneMirror\Core\JsonStore;

final class FileClusterRepository implements ClusterRepository
{
    public function __construct(private readonly JsonStore $store) {}
    public function find(string $id): ?array { return $this->store->get('story-clusters', $id); }
    public function save(array $cluster): array { return $this->store->put('story-clusters', $cluster); }
    public function all(): array
    {
        $rows = $this->store->all('story-clusters');
        usort($rows, fn($a,$b) => strcmp((string)($b['last_updated'] ?? $b['updated_at'] ?? ''), (string)($a['last_updated'] ?? $a['updated_at'] ?? '')));
        return $rows;
    }
    public function containing(string $contentId): ?array
    {
        foreach ($this->all() as $cluster) {
            if (in_array($contentId, $cluster['content_ids'] ?? [], true)) return $cluster;
        }
        return null;
    }
}
