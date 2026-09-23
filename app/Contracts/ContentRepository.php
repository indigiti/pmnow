<?php
namespace PuneMirror\Contracts;

interface ContentRepository
{
    public function find(string $id): ?array;
    public function save(array $content): array;
    public function all(): array;
    public function findByExternal(string $sourceId, string $externalId): ?array;
}
