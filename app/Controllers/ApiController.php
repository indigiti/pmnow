<?php
namespace PuneMirror\Controllers;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\Request;
use PuneMirror\Core\Response;
use PuneMirror\Core\UuidV7;
use PuneMirror\Runtime\RuntimeStore;
use PuneMirror\Services\StoryService;
use PuneMirror\Services\UserStateService;
use PuneMirror\Services\SearchIndexService;
use PuneMirror\Services\NotificationService;

final class ApiController
{
    public function __construct(
        private readonly StoryService $stories,
        private readonly UserStateService $users,
        private readonly JsonStore $store,
        private readonly RuntimeStore $runtime,
        private readonly array $config,
        private readonly SearchIndexService $searchIndex,
        private readonly NotificationService $notifications
    ) {}

    public function health(): never
    {
        Response::json([
            'status' => 'healthy',
            'persistence' => $this->config['persistence'],
            'runtime' => $this->config['runtime'],
            'time' => gmdate('c'),
        ]);
    }

    public function feed(Request $request): never
    {
        $limit = max(1, min(50, (int)($request->query['limit'] ?? 12)));
        $rows = $this->stories->feed($limit);
        $channel = strtolower((string)($request->query['channel'] ?? ''));
        if ($channel && $channel !== 'for-you') {
            $rows = array_values(array_filter($rows, function($s) use ($channel) {
                $hay = strtolower(($s['headline'] ?? '') . ' ' . implode(' ', array_column($s['categories'] ?? [], 'slug')) . ' ' . implode(' ', array_column($s['locations'] ?? [], 'slug')));
                return str_contains($hay, $channel);
            }));
        }
        Response::json($rows, 200, ['has_more' => false, 'next_cursor' => null]);
    }

    public function story(string $id): never
    {
        $story = $this->stories->find($id);
        if (!$story) Response::error('STORY_NOT_FOUND', 'Story was not found', 404);
        Response::json($story);
    }

    public function search(Request $request): never
    {
        $q=(string)($request->query['q'] ?? '');
        Response::json($this->searchIndex->query($q, max(1,min(50,(int)($request->query['limit'] ?? 20)))));
    }

    public function reels(): never { Response::json($this->stories->byType('reel', 20)); }

    public function liveUpdates(Request $request, string $liveId): never
    {
        $after = isset($request->query['after']) ? (string)$request->query['after'] : null;
        Response::json($this->stories->liveUpdates($liveId, $after));
    }

    public function demoLiveUpdate(string $liveId): never
    {
        $templates = [
            ['Traffic movement improves in central Pune', 'Traffic police report steadily improving movement after the latest diversions.'],
            ['Fresh advisory issued for low-lying roads', 'Commuters are advised to avoid waterlogged internal roads and use main corridors.'],
            ['PMPML adjusts selected routes', 'A small number of bus routes have been temporarily diverted while crews clear standing water.'],
        ];
        $pick = $templates[array_rand($templates)];
        $row = $this->store->put('live-updates', [
            'id' => UuidV7::generate(),
            'live_story_id' => $liveId,
            'type' => 'traffic',
            'headline' => $pick[0],
            'body' => $pick[1],
            'published_at' => gmdate('c'),
        ]);
        $this->runtime->publish('live:' . $liveId, $row);
        foreach ($this->store->all('live-stories') as $liveStory) {
            if (($liveStory['id'] ?? '') === $liveId && !empty($liveStory['story_id'])) {
                $this->notifications->notifyFollowers((string)$liveStory['story_id'], 'live_update', $pick[0], $pick[1]);
                break;
            }
        }
        Response::json($row, 201);
    }

    public function me(): never { Response::json($this->users->state()); }

    public function toggleBookmark(string $storyId): never
    {
        $active = $this->users->toggleBookmark($storyId);
        Response::json(['story_id' => $storyId, 'bookmarked' => $active]);
    }

    public function toggleFollow(string $storyId): never
    {
        $active = $this->users->toggleFollow($storyId);
        Response::json(['story_id' => $storyId, 'followed' => $active]);
    }

    public function stream(string $liveId): never
    {
        if (!$this->config['sse_enabled']) Response::error('SSE_DISABLED', 'SSE is disabled; client polling fallback is active.', 503);
        @set_time_limit(30);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        $last = '';
        $started = time();
        while ((time() - $started) < 25) {
            $updates = $this->stories->liveUpdates($liveId, $last ?: null);
            if ($updates) {
                foreach (array_reverse($updates) as $update) {
                    $last = max($last, (string)($update['published_at'] ?? ''));
                    echo 'data: ' . json_encode($update, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
                }
                @ob_flush(); @flush();
            } else {
                echo ": heartbeat\n\n"; @ob_flush(); @flush();
            }
            sleep(3);
        }
        exit;
    }
}
