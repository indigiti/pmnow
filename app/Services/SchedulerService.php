<?php
namespace PuneMirror\Services;

use PuneMirror\Contracts\JobRepository;
use PuneMirror\Contracts\SourceRepository;
use PuneMirror\Core\UuidV7;
use PuneMirror\Core\JsonStore;

final class SchedulerService
{
    public function __construct(private readonly SourceRepository $sources, private readonly JobRepository $jobs, private readonly ?JsonStore $store=null) {}

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
        if($this->store)$queued=array_merge($queued,$this->queueReaderDigests($now));
        return $queued;
    }

    private function queueReaderDigests(int $now):array
    {
        $queued=[];
        foreach($this->store?->all('users')??[] as $user){
            if(($user['role']??'reader')!=='reader')continue;
            $prefs=array_merge([
                'enabled'=>true,'morning_digest'=>false,'evening_digest'=>false,'weekend_digest'=>false,'timezone'=>'Asia/Kolkata'
            ],(array)($user['notification_preferences']??[]));
            if(!($prefs['enabled']??true))continue;
            try{$tz=new \DateTimeZone((string)$prefs['timezone']);}catch(\Throwable){$tz=new \DateTimeZone('Asia/Kolkata');}
            $local=(new \DateTimeImmutable('@'.$now))->setTimezone($tz);
            $hour=(int)$local->format('G');$dow=(int)$local->format('N');$date=$local->format('Y-m-d');
            $period=null;$jobType=null;
            if(($prefs['weekend_digest']??false)&&in_array($dow,[6,7],true)&&$hour>=7&&$hour<11){$period='weekend';$jobType='READER_DIGEST_WEEKEND';}
            elseif(($prefs['morning_digest']??false)&&$hour>=7&&$hour<10){$period='morning';$jobType='READER_DIGEST_MORNING';}
            elseif(($prefs['evening_digest']??false)&&$hour>=17&&$hour<21){$period='evening';$jobType='READER_DIGEST_EVENING';}
            if(!$period||!$jobType)continue;
            if(($user['distribution_state']['last_'.$period.'_digest_date']??null)===$date)continue;
            if($this->jobs->hasPending($jobType,(string)$user['id']))continue;
            $queued[]=$this->jobs->save([
                'id'=>UuidV7::generate(),'type'=>$jobType,'subject_id'=>$user['id'],'status'=>'queued',
                'attempts'=>0,'max_attempts'=>3,'available_at'=>gmdate('c'),
                'payload'=>['user_id'=>$user['id'],'period'=>$period],
            ]);
        }
        return $queued;
    }
}
