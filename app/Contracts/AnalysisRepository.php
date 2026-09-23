<?php
namespace PuneMirror\Contracts;

interface AnalysisRepository
{
    public function save(array $analysis): array;
    public function forContent(string $contentId): array;
    public function all(): array;
}
