<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;

final class ErrorCenterService
{
    public function __construct(private readonly JsonStore $store) {}
    public function capture(string $code, string $message, array $context=[]): array
    {
        foreach ($context as $k=>$v) if (preg_match('/token|secret|password|credential|authorization/i',(string)$k)) $context[$k]='[REDACTED]';
        return $this->store->put('system-errors',['id'=>UuidV7::generate(),'code'=>$code,'message'=>substr($message,0,2000),'context'=>$context,'status'=>'open','occurred_at'=>gmdate('c')]);
    }
    public function all(): array { $r=$this->store->all('system-errors'); usort($r,fn($a,$b)=>strcmp((string)($b['occurred_at']??''),(string)($a['occurred_at']??''))); return $r; }
    public function resolve(string $id): ?array { $r=$this->store->get('system-errors',$id); if(!$r)return null; $r['status']='resolved';$r['resolved_at']=gmdate('c');return $this->store->put('system-errors',$r); }
}
