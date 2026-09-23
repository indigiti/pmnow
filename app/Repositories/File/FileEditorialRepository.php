<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\EditorialRepository;
use PuneMirror\Core\JsonStore;

final class FileEditorialRepository implements EditorialRepository
{
    public function __construct(private readonly JsonStore $store) {}

    public function saveAction(array $action): array
    {
        $saved = $this->store->put('editorial-actions', $action);
        $this->store->appendEvent('editorial', [
            'event' => 'editorial.action',
            'action_id' => $saved['id'],
            'content_id' => $saved['content_id'] ?? null,
            'action' => $saved['action'] ?? null,
            'actor_id' => $saved['actor_id'] ?? null,
        ]);
        return $saved;
    }

    public function actionsForContent(string $contentId): array
    {
        $rows = array_values(array_filter($this->store->all('editorial-actions'), fn($r) => ($r['content_id'] ?? '') === $contentId));
        usort($rows, fn($a,$b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $rows;
    }

    public function allActions(): array
    {
        $rows = $this->store->all('editorial-actions');
        usort($rows, fn($a,$b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $rows;
    }
}
