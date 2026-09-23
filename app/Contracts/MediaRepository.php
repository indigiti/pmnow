<?php
namespace PuneMirror\Contracts;

interface MediaRepository
{
    public function find(string $id): ?array;
    public function save(array $media): array;
    public function findMany(array $ids): array;
}
