<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\StoryRepository;
use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;

final class FileStoryRepository implements StoryRepository
{
    public function __construct(private readonly JsonStore $store, private readonly IndexManager $indexes) {}

    public function find(string $id): ?array { return $this->store->get('stories', $id); }

    public function save(array $story): array
    {
        $saved = $this->store->put('stories', $story);
        $this->rebuildIndexes();
        $this->store->appendEvent('stories', ['event' => 'story.saved', 'story_id' => $saved['id']]);
        return $saved;
    }

    public function latest(int $limit = 20, ?string $type = null): array
    {
        return $this->page($limit, $type)['rows'];
    }

    public function page(int $limit = 20, ?string $type = null, ?string $cursor = null): array
    {
        $limit=max(1,min(50,$limit));
        $name = $type ? 'type-' . $type : 'feed-latest';
        $index = $this->indexes->read($name);
        if (!$index) { $this->rebuildIndexes(); $index = $this->indexes->read($name); }
        $ids=array_values($index['ids']??[]);
        $offset=0;
        if($cursor){
            $position=array_search($cursor,$ids,true);
            if($position!==false)$offset=$position+1;
        }
        $slice=array_slice($ids,$offset,$limit+1);
        $hasMore=count($slice)>$limit;
        if($hasMore)array_pop($slice);
        $rows=[];foreach($slice as $id)if($row=$this->find((string)$id))$rows[]=$row;
        $nextCursor=$hasMore&&$slice?(string)end($slice):null;
        return ['rows'=>$rows,'has_more'=>$hasMore,'next_cursor'=>$nextCursor];
    }

    public function all(): array
    {
        $rows = $this->store->all('stories');
        usort($rows, fn($a, $b) => strcmp($b['published_at'] ?? $b['created_at'] ?? '', $a['published_at'] ?? $a['created_at'] ?? ''));
        return $rows;
    }

    public function rebuildIndexes(): void
    {
        $rows = $this->all();
        $this->indexes->write('feed-latest', ['ids' => array_values(array_column(array_filter($rows, fn($s) => ($s['status'] ?? '') === 'published'), 'id'))]);
        $types = [];
        foreach ($rows as $row) {
            if (($row['status'] ?? '') !== 'published') continue;
            $types[$row['type'] ?? 'article'][] = $row['id'];
        }
        foreach ($types as $type => $ids) $this->indexes->write('type-' . $type, ['ids' => array_values($ids)]);
    }
}
