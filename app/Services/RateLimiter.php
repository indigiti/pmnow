<?php
namespace PuneMirror\Services;

use PuneMirror\Runtime\RuntimeStore;

final class RateLimiter
{
    public function __construct(private readonly RuntimeStore $runtime){}
    public function hit(string $bucket,string $subject,int $limit,int $windowSeconds):array
    {
        $key='rate:'.hash('sha256',$bucket.'|'.$subject);
        $row=$this->runtime->incrementWindow($key,max(1,$windowSeconds));
        $count=(int)($row['count']??0);$reset=(int)($row['reset_at']??time()+$windowSeconds);
        return ['allowed'=>$count<=$limit,'limit'=>$limit,'remaining'=>max(0,$limit-$count),'reset_at'=>$reset];
    }
}
