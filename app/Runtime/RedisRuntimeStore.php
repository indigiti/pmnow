<?php
namespace PuneMirror\Runtime;

final class RedisRuntimeStore implements RuntimeStore
{
    public function __construct(private readonly RedisClient $redis) {}
    public function get(string $key): mixed { $raw=$this->redis->get($key); return $raw===null?null:json_decode($raw,true); }
    public function set(string $key,mixed $value,int $ttl=300):void{$this->redis->setex($key,$ttl,json_encode($value,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));}
    public function publish(string $channel,array $payload):void{$this->redis->publish($channel,json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));}
}
