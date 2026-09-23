<?php
namespace PuneMirror\Runtime;

final class FileRuntimeStore implements RuntimeStore
{
    public function __construct(private readonly string $root) { if (!is_dir($root)) mkdir($root,0775,true); }
    public function get(string $key): mixed
    {
        $path=$this->path($key); if(!is_file($path)) return null;
        $row=json_decode((string)file_get_contents($path),true);
        if(!$row || ($row['expires_at']??0)<time()){ @unlink($path); return null; }
        return $row['value']??null;
    }
    public function set(string $key, mixed $value, int $ttl = 300): void
    {
        file_put_contents($this->path($key),json_encode(['expires_at'=>time()+$ttl,'value'=>$value],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),LOCK_EX);
    }
    public function publish(string $channel, array $payload): void
    {
        $file=$this->root.'/events.jsonl';
        file_put_contents($file,json_encode(['channel'=>$channel,'payload'=>$payload,'at'=>gmdate('c')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n",FILE_APPEND|LOCK_EX);
    }
    private function path(string $key): string { return $this->root.'/'.hash('sha256',$key).'.json'; }
}
