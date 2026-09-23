<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\AnalysisRepository;
use PuneMirror\Core\JsonStore;

final class FileAnalysisRepository implements AnalysisRepository
{
    public function __construct(private readonly JsonStore $store) {}
    public function save(array $analysis): array { return $this->store->put('analysis-results', $analysis); }
    public function forContent(string $contentId): array
    {
        $rows = array_values(array_filter($this->store->all('analysis-results'), fn($r) => ($r['content_id'] ?? '') === $contentId));
        usort($rows, fn($a,$b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $rows;
    }
    public function all(): array { return $this->store->all('analysis-results'); }
}
