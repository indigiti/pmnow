<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\SourceRepository;
use PuneMirror\Core\JsonStore;

final class FileSourceRepository implements SourceRepository
{
    public function __construct(private readonly JsonStore $store) {}
    public function find(string $id): ?array { return $this->store->get('sources', $id); }
    public function save(array $source): array { return $this->store->put('sources', $source); }
    public function all(): array
    {
        $rows = $this->store->all('sources');
        usort($rows, fn($a,$b) => strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')));
        return $rows;
    }
    public function delete(string $id): bool { return $this->store->delete('sources', $id); }
}
