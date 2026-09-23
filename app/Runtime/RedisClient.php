<?php
namespace PuneMirror\Runtime;

final class RedisClient
{
    private $socket=null;
    public function __construct(private readonly string $host,private readonly int $port,private readonly string $prefix='pmnow:'){}
    public function available():bool{try{$this->connect();$this->command(['PING']);return true;}catch(\Throwable){return false;}}
    public function get(string $key):?string{$v=$this->command(['GET',$this->prefix.$key]);return is_string($v)?$v:null;}
    public function setex(string $key,int $ttl,string $value):void{$this->command(['SETEX',$this->prefix.$key,(string)$ttl,$value]);}
    public function publish(string $channel,string $value):void{$this->command(['PUBLISH',$this->prefix.$channel,$value]);}
    public function incrementWindow(string $key,int $windowSeconds):array
    {
        $script="local c=redis.call('INCR',KEYS[1]); if c==1 then redis.call('EXPIRE',KEYS[1],ARGV[1]); end; local t=redis.call('TTL',KEYS[1]); return {c,t}";
        $r=$this->command(['EVAL',$script,'1',$this->prefix.$key,(string)max(1,$windowSeconds)]);
        if(!is_array($r)||count($r)<2)throw new \RuntimeException('Redis counter response invalid');
        $ttl=max(1,(int)$r[1]);return ['count'=>(int)$r[0],'reset_at'=>time()+$ttl];
    }
    private function connect():void
    {
        if(is_resource($this->socket))return;
        $this->socket=@stream_socket_client("tcp://{$this->host}:{$this->port}",$errno,$errstr,0.25);
        if(!$this->socket)throw new \RuntimeException("Redis unavailable: $errstr");
        stream_set_timeout($this->socket,1);
    }
    private function command(array $parts):mixed
    {
        $this->connect();$cmd='*'.count($parts)."\r\n";
        foreach($parts as $p){$p=(string)$p;$cmd.='$'.strlen($p)."\r\n".$p."\r\n";}
        if(fwrite($this->socket,$cmd)===false)throw new \RuntimeException('Redis write failed');
        return $this->readReply();
    }
    private function readReply():mixed
    {
        $line=fgets($this->socket);if($line===false)throw new \RuntimeException('Redis read failed');
        $type=$line[0];$payload=rtrim(substr($line,1),"\r\n");
        return match($type){
            '+'=>$payload,
            ':'=> (int)$payload,
            '$'=>$this->readBulk((int)$payload),
            '*'=>$this->readArray((int)$payload),
            '-'=>throw new \RuntimeException('Redis: '.$payload),
            default=>null,
        };
    }
    private function readBulk(int $len):?string
    {
        if($len<0)return null;$data='';
        while(strlen($data)<$len){$chunk=fread($this->socket,$len-strlen($data));if($chunk===false||$chunk==='')throw new \RuntimeException('Redis bulk read failed');$data.=$chunk;}
        fread($this->socket,2);return $data;
    }
    private function readArray(int $len):?array
    {
        if($len<0)return null;$out=[];for($i=0;$i<$len;$i++)$out[]=$this->readReply();return $out;
    }
}
