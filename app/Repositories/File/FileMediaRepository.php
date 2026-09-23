<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\MediaRepository;
use PuneMirror\Core\JsonStore;

final class FileMediaRepository implements MediaRepository
{
    public function __construct(private readonly JsonStore $store) {}
    public function find(string $id): ?array { return $this->store->get('media', $id); }
    public function save(array $media): array { return $this->store->put('media', $media); }
    public function findMany(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) if ($row = $this->find((string)$id)) $out[] = $row;
        usort($out, fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));
        return $out;
    }
}
