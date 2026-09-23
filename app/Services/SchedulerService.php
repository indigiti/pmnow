<?php
namespace PuneMirror\Services;

use PuneMirror\Contracts\JobRepository;
use PuneMirror\Contracts\SourceRepository;
use PuneMirror\Core\UuidV7;

final class SchedulerService
{
    public function __construct(private readonly SourceRepository $sources, private readonly JobRepository $jobs) {}

    public function tick(): array
    {
        $queued=[]; $now=time();
        foreach ($this->sources->all() as $source) {
            if (!($source['enabled'] ?? false) || !($source['sync_enabled'] ?? false)) continue;
            $last = isset($source['last_sync_at']) && $source['last_sync_at'] ? strtotime((string)$source['last_sync_at']) : 0;
            $interval = max(60, (int)($source['sync_interval'] ?? 300));
            if ($last && ($last + $interval) > $now) continue;
            if ($this->jobs->hasPending('SOURCE_SYNC', (string)$source['id'])) continue;
            $queued[]=$this->jobs->save([
                'id'=>UuidV7::generate(),'type'=>'SOURCE_SYNC','subject_id'=>$source['id'],'status'=>'queued',
                'attempts'=>0,'max_attempts'=>5,'available_at'=>gmdate('c'),'payload'=>['source_id'=>$source['id'],'limit'=>10],
            ]);
        }
        return $queued;
    }
}
