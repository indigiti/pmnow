<?php
namespace PuneMirror\Services;

use PuneMirror\Contracts\ContentRepository;
use PuneMirror\Contracts\EditorialRepository;
use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;
use RuntimeException;

final class EditorialService
{
    public function __construct(
        private readonly ContentRepository $contents,
        private readonly EditorialRepository $actions,
        private readonly ContentHubService $hub,
        private readonly JsonStore $store
    ) {}

    public function inbox(?string $status = null): array
    {
        $rows = $this->contents->all();
        if ($status) $rows = array_values(array_filter($rows, fn($r) => ($r['workflow_status'] ?? 'ready') === $status));
        return array_map(function(array $row): array {
            $row['editorial_actions'] = $this->actions->actionsForContent((string)$row['id']);
            $row['source'] = isset($row['source_id']) ? $this->store->get('sources', (string)$row['source_id']) : null;
            return $row;
        }, $rows);
    }

    public function metrics(): array
    {
        $counts = ['new'=>0,'analyzing'=>0,'ready'=>0,'review_required'=>0,'published'=>0,'rejected'=>0,'duplicate'=>0,'archived'=>0,'hidden'=>0,'error'=>0];
        foreach ($this->contents->all() as $row) {
            $s = strtolower((string)($row['workflow_status'] ?? 'ready'));
            $counts[$s] = ($counts[$s] ?? 0) + 1;
        }
        return $counts;
    }

    public function act(string $contentId, string $action, array $actor, array $changes = [], ?string $reason = null): array
    {
        $content = $this->contents->find($contentId);
        if (!$content) throw new RuntimeException('Content not found');
        $before = $content;
        $action = strtolower(trim($action));

        if ($action === 'edit') {
            foreach (['title','body','caption','summary','final_category_slugs','final_location_slugs','entity_names'] as $field) {
                if (array_key_exists($field, $changes)) $content[$field] = $changes[$field];
            }
            $content = $this->contents->save($content);
        } elseif ($action === 'approve' || $action === 'publish') {
            $result = $this->hub->publishExisting($contentId);
            $content = $result['content'];
        } else {
            $map = [
                'reject' => 'rejected',
                'archive' => 'archived',
                'hide' => 'hidden',
                'mark_duplicate' => 'duplicate',
                'review' => 'review_required',
                'ready' => 'ready',
            ];
            if (!isset($map[$action])) throw new RuntimeException('Unsupported editorial action');
            $content['workflow_status'] = $map[$action];
            $content = $this->contents->save($content);
        }

        $audit = $this->actions->saveAction([
            'id' => UuidV7::generate(),
            'content_id' => $contentId,
            'action' => $action,
            'actor_id' => $actor['id'] ?? null,
            'actor_name' => $actor['name'] ?? $actor['email'] ?? 'Editor',
            'reason' => $reason,
            'before_status' => $before['workflow_status'] ?? null,
            'after_status' => $content['workflow_status'] ?? null,
            'changes' => $changes,
        ]);
        return ['content' => $content, 'action' => $audit];
    }

    public function bulk(array $contentIds, string $action, array $actor, array $changes = []): array
    {
        $results = [];
        foreach (array_values(array_unique(array_map('strval', $contentIds))) as $id) {
            try { $results[] = ['id'=>$id,'ok'=>true,'result'=>$this->act($id,$action,$actor,$changes)]; }
            catch (\Throwable $e) { $results[] = ['id'=>$id,'ok'=>false,'error'=>$e->getMessage()]; }
        }
        return $results;
    }
}
