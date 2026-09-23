<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\UserStateRepository;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;

final class FileUserStateRepository implements UserStateRepository
{
    public function __construct(private readonly JsonStore $store) {}

    public function user(string $id): ?array { return $this->store->get('users', $id); }
    public function saveUser(array $user): array { return $this->store->put('users', $user); }

    public function bookmarks(string $userId): array
    {
        return array_values(array_filter($this->store->all('bookmarks'), fn($r) => ($r['user_id'] ?? '') === $userId));
    }

    public function follows(string $userId): array
    {
        return array_values(array_filter($this->store->all('follows'), fn($r) => ($r['user_id'] ?? '') === $userId));
    }

    public function toggleBookmark(string $userId, string $storyId): bool
    {
        foreach ($this->bookmarks($userId) as $row) {
            if (($row['story_id'] ?? '') === $storyId) { $this->store->delete('bookmarks', $row['id']); return false; }
        }
        $this->store->put('bookmarks', ['id' => UuidV7::generate(), 'user_id' => $userId, 'story_id' => $storyId]);
        return true;
    }

    public function toggleFollow(string $userId, string $storyId): bool
    {
        foreach ($this->follows($userId) as $row) {
            if (($row['story_id'] ?? '') === $storyId) { $this->store->delete('follows', $row['id']); return false; }
        }
        $this->store->put('follows', ['id' => UuidV7::generate(), 'user_id' => $userId, 'story_id' => $storyId, 'follow_type' => 'story']);
        return true;
    }
}
