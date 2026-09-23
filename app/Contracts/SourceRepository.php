<?php
namespace PuneMirror\Contracts;

interface SourceRepository
{
    public function find(string $id): ?array;
    public function save(array $source): array;
    public function all(): array;
    public function delete(string $id): bool;
}
