<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;

final class QuotaService
{
    public function __construct(private readonly JsonStore $store) {}
    public function record(string $sourceId, int $calls=1, int $softLimit=1000): array
    {
        $day=gmdate('Y-m-d'); $row=$this->find($sourceId,$day) ?? ['id'=>UuidV7::generate(),'source_id'=>$sourceId,'day'=>$day,'requests'=>0,'soft_limit'=>$softLimit];
        $row['requests']=(int)$row['requests']+$calls; $row['soft_limit']=max(1,$softLimit); $row['last_request_at']=gmdate('c'); return $this->store->put('api-usage',$row);
    }
    public function state(string $sourceId, int $softLimit=1000): array
    {
        $r=$this->find($sourceId,gmdate('Y-m-d')); $used=(int)($r['requests']??0); $limit=max(1,(int)($r['soft_limit']??$softLimit)); $pct=$used/$limit;
        $status=$pct>=.95?'throttled':($pct>=.85?'quota_low':($pct>=.70?'watch':'healthy'));
        return ['requests'=>$used,'soft_limit'=>$limit,'ratio'=>$pct,'status'=>$status];
    }
    private function find(string $sourceId,string $day):?array{foreach($this->store->all('api-usage') as $r)if(($r['source_id']??'')===$sourceId&&($r['day']??'')===$day)return $r;return null;}
}
