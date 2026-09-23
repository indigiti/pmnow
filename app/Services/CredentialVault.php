<?php
namespace PuneMirror\Services;

use RuntimeException;

final class CredentialVault
{
    private string $key;
    public function __construct(private readonly string $root, string $appKey)
    {
        if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) throw new RuntimeException('Cannot create secret vault directory');
        if (strlen($appKey) < 24) throw new RuntimeException('APP_KEY must be at least 24 characters');
        $this->key = hash('sha256', $appKey, true);
    }

    public function put(string $sourceId, array $secrets): array
    {
        $current = $this->all($sourceId);
        foreach ($secrets as $name=>$value) {
            if (!preg_match('/^[A-Za-z0-9_.-]{1,80}$/',(string)$name)) throw new RuntimeException('Invalid credential name');
            if ($value === null || $value === '') unset($current[(string)$name]); else $current[(string)$name]=(string)$value;
        }
        $plain=json_encode($current,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
        $iv=random_bytes(12); $tag='';
        $cipher=openssl_encrypt($plain,'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,$iv,$tag);
        if ($cipher===false) throw new RuntimeException('Credential encryption failed');
        $payload=json_encode(['v'=>1,'iv'=>base64_encode($iv),'tag'=>base64_encode($tag),'data'=>base64_encode($cipher)],JSON_THROW_ON_ERROR);
        $tmp=$this->path($sourceId).'.tmp.'.bin2hex(random_bytes(3)); file_put_contents($tmp,$payload,LOCK_EX); chmod($tmp,0600); rename($tmp,$this->path($sourceId));
        return $this->masked($sourceId);
    }

    public function all(string $sourceId): array
    {
        $path=$this->path($sourceId); if(!is_file($path)) return [];
        $p=json_decode((string)file_get_contents($path),true); if(!is_array($p)) throw new RuntimeException('Invalid credential vault record');
        $plain=openssl_decrypt(base64_decode((string)$p['data'],true),'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,base64_decode((string)$p['iv'],true),base64_decode((string)$p['tag'],true));
        if($plain===false) throw new RuntimeException('Credential decryption failed');
        $data=json_decode($plain,true,512,JSON_THROW_ON_ERROR); return is_array($data)?$data:[];
    }

    public function masked(string $sourceId): array
    {
        $out=[]; foreach($this->all($sourceId) as $name=>$value){$s=(string)$value;$out[$name]=strlen($s)<=4?'••••':'••••'.substr($s,-4);} return $out;
    }
    public function delete(string $sourceId): void { @unlink($this->path($sourceId)); }
    private function path(string $id): string { if(!preg_match('/^[0-9a-f-]+$/i',$id)) throw new RuntimeException('Invalid source id'); return $this->root.'/'.strtolower($id).'.vault'; }
}
