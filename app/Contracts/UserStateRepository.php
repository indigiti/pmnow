<?php
namespace PuneMirror\Contracts;

interface UserStateRepository
{
    public function user(string $id): ?array;
    public function saveUser(array $user): array;
    public function bookmarks(string $userId): array;
    public function follows(string $userId): array;
    public function toggleBookmark(string $userId, string $storyId): bool;
    public function toggleFollow(string $userId, string $storyId): bool;
}
