<?php
namespace PuneMirror\Services;

final class UtilityFreshnessService
{
    public function state(array $update,?int $now=null):array
    {
        $now??=time();$verified=strtotime((string)($update['verified_at']??$update['published_at']??''));
        $ttl=max(60,(int)($update['metadata']['freshness_ttl_seconds']??$this->defaultTtl((string)($update['entity']['kind']??''))));
        if(!$verified)return ['state'=>'unknown','age_seconds'=>null,'ttl_seconds'=>$ttl];
        $age=max(0,$now-$verified);
        return ['state'=>$age<=$ttl?'live':($age<=$ttl*3?'stale':'expired'),'age_seconds'=>$age,'ttl_seconds'=>$ttl];
    }

    public function defaultTtl(string $kind):int
    {
        return match($kind){'traffic','transit'=>900,'weather','air'=>1800,'outage','emergency'=>900,'event'=>21600,default=>3600};
    }
}
