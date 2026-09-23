<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\JobRepository;
use PuneMirror\Core\JsonStore;

final class FileJobRepository implements JobRepository
{
    public function __construct(private readonly JsonStore $store) {}
    public function save(array $job): array { return $this->store->put('jobs', $job); }
    public function find(string $id): ?array { return $this->store->get('jobs', $id); }
    public function all(): array
    {
        $rows = $this->store->all('jobs');
        usort($rows, fn($a,$b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $rows;
    }
    public function nextQueued(): ?array
    {
        $rows = array_values(array_filter($this->all(), fn($r) => ($r['status'] ?? '') === 'queued'));
        usort($rows, fn($a,$b) => strcmp((string)($a['available_at'] ?? $a['created_at'] ?? ''), (string)($b['available_at'] ?? $b['created_at'] ?? '')));
        $now = gmdate('c');
        foreach ($rows as $row) {
            if (($row['available_at'] ?? '') === '' || strcmp((string)$row['available_at'], $now) <= 0) return $row;
        }
        return null;
    }
    public function hasPending(string $type, ?string $subjectId = null): bool
    {
        foreach ($this->all() as $row) {
            if (($row['type'] ?? '') !== $type) continue;
            if (!in_array(($row['status'] ?? ''), ['queued','processing'], true)) continue;
            if ($subjectId !== null && ($row['subject_id'] ?? null) !== $subjectId) continue;
            return true;
        }
        return false;
    }
}
