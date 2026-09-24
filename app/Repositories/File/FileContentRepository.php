<?php
namespace PuneMirror\Repositories\File;

use PuneMirror\Contracts\ContentRepository;
use PuneMirror\Core\IndexManager;
use PuneMirror\Core\JsonStore;

final class FileContentRepository implements ContentRepository
{
    public function __construct(private readonly JsonStore $store, private readonly ?IndexManager $indexes=null) {}
    public function find(string $id): ?array { return $this->store->get('contents', $id); }

    public function save(array $content): array
    {
        $saved = $this->store->put('contents', $content);
        if($this->indexes){
            $sourceId=(string)($saved['source_id']??'');$externalId=(string)($saved['external_id']??'');
            if($sourceId!==''&&$externalId!==''){
                $index=$this->indexes->read('content-external')??['map'=>[]];
                $index['map'][$this->externalKey($sourceId,$externalId)]=(string)$saved['id'];
                $this->indexes->write('content-external',$index);
            }
        }
        $this->store->appendEvent('contents', ['event' => 'content.saved', 'content_id' => $saved['id'], 'source_id' => $saved['source_id'] ?? null]);
        return $saved;
    }

    public function all(): array
    {
        $rows = $this->store->all('contents');
        usort($rows, fn($a,$b) => strcmp((string)($b['published_at'] ?? $b['created_at'] ?? ''), (string)($a['published_at'] ?? $a['created_at'] ?? '')));
        return $rows;
    }

    public function findByExternal(string $sourceId, string $externalId): ?array
    {
        if($this->indexes){
            $index=$this->indexes->read('content-external');
            $id=$index['map'][$this->externalKey($sourceId,$externalId)]??null;
            if(is_string($id)&&$id!==''&&($row=$this->find($id)))return $row;
            $this->rebuildExternalIndex();
            $index=$this->indexes->read('content-external');
            $id=$index['map'][$this->externalKey($sourceId,$externalId)]??null;
            if(is_string($id)&&$id!==''&&($row=$this->find($id)))return $row;
            return null;
        }
        foreach ($this->all() as $row) if (($row['source_id'] ?? '') === $sourceId && ($row['external_id'] ?? '') === $externalId) return $row;
        return null;
    }

    private function rebuildExternalIndex():void
    {
        if(!$this->indexes)return;
        $map=[];
        foreach($this->store->all('contents') as $row){
            $sourceId=(string)($row['source_id']??'');$externalId=(string)($row['external_id']??'');
            if($sourceId!==''&&$externalId!=='')$map[$this->externalKey($sourceId,$externalId)]=(string)$row['id'];
        }
        $this->indexes->write('content-external',['map'=>$map]);
    }

    private function externalKey(string $sourceId,string $externalId):string{return hash('sha256',$sourceId."\0".$externalId);}
}
