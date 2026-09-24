<?php
namespace PuneMirror\Contracts;

interface StoryRepository
{
    public function find(string $id): ?array;
    public function save(array $story): array;
    public function latest(int $limit = 20, ?string $type = null): array;
    public function page(int $limit = 20, ?string $type = null, ?string $cursor = null): array;
    public function all(): array;
}
