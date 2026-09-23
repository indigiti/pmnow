<?php
namespace PuneMirror\Runtime;

final class FileRuntimeStore implements RuntimeStore
{
    public function __construct(private readonly string $root){if(!is_dir($root))mkdir($root,0775,true);}
    public function get(string $key):mixed
    {
        $path=$this->path($key);if(!is_file($path))return null;
        $row=json_decode((string)file_get_contents($path),true);
        if(!$row||($row['expires_at']??0)<time()){@unlink($path);return null;}
        return $row['value']??null;
    }
    public function set(string $key,mixed $value,int $ttl=300):void
    {
        $path=$this->path($key);$lock=$this->lock($path);
        try{$this->writeAtomic($path,['expires_at'=>time()+max(1,$ttl),'value'=>$value]);}
        finally{$this->unlock($lock);}
    }
    public function incrementWindow(string $key,int $windowSeconds):array
    {
        $path=$this->path($key);$lock=$this->lock($path);$now=time();
        try{
            $row=is_file($path)?json_decode((string)file_get_contents($path),true):null;
            $expires=(int)($row['expires_at']??0);$value=$row['value']??0;
            if(!$row||$expires<=$now){$count=0;$expires=$now+max(1,$windowSeconds);}
            else{$count=is_array($value)?(int)($value['count']??0):(int)$value;if(is_array($value)&&isset($value['reset_at']))$expires=(int)$value['reset_at'];}
            $count++;
            $this->writeAtomic($path,['expires_at'=>$expires,'value'=>$count]);
            return ['count'=>$count,'reset_at'=>$expires];
        }finally{$this->unlock($lock);}
    }
    public function publish(string $channel,array $payload):void
    {
        $file=$this->root.'/events.jsonl';
        file_put_contents($file,json_encode(['channel'=>$channel,'payload'=>$payload,'at'=>gmdate('c')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n",FILE_APPEND|LOCK_EX);
    }
    private function path(string $key):string{return $this->root.'/'.hash('sha256',$key).'.json';}
    private function lock(string $path)
    {
        $lock=fopen($path.'.lock','c+');if(!$lock||!flock($lock,LOCK_EX))throw new \RuntimeException('Cannot lock runtime store');return $lock;
    }
    private function unlock($lock):void{@flock($lock,LOCK_UN);@fclose($lock);}
    private function writeAtomic(string $path,array $row):void
    {
        $tmp=$path.'.'.bin2hex(random_bytes(4)).'.tmp';
        try{
            $json=json_encode($row,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            if(file_put_contents($tmp,$json,LOCK_EX)===false)throw new \RuntimeException('Cannot write runtime store');
            if(!rename($tmp,$path))throw new \RuntimeException('Cannot replace runtime store record');
        }finally{if(is_file($tmp))@unlink($tmp);}
    }
}
