<?php
namespace PuneMirror\Contracts;

interface ClusterRepository
{
    public function find(string $id): ?array;
    public function save(array $cluster): array;
    public function all(): array;
    public function containing(string $contentId): ?array;
}
