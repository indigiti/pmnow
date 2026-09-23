<?php
namespace PuneMirror\Contracts;

interface JobRepository
{
    public function save(array $job): array;
    public function find(string $id): ?array;
    public function all(): array;
    public function nextQueued(): ?array;
    public function claimNext(): ?array;
    public function hasPending(string $type, ?string $subjectId = null): bool;
}
