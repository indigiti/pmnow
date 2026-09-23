<?php
namespace PuneMirror\Services;

use PuneMirror\Contracts\SourceRepository;
use PuneMirror\Core\PythonClient;
use PuneMirror\Core\UrlGuard;
use PuneMirror\Core\UuidV7;
use RuntimeException;

final class SourceService
{
    public function __construct(
        private readonly SourceRepository $sources,
        private readonly PythonClient $python,
        private readonly ?CredentialVault $vault=null,
        private readonly ?QuotaService $quota=null,
        private readonly ?ErrorCenterService $errors=null,
        private readonly bool $allowPrivateUrls=false
    ) {}

    public function all(): array
    {
        return array_map(function(array $s): array {
            if($this->vault) $s['credential_status']=$this->vault->masked((string)$s['id']);
            if($this->quota) $s['quota']=$this->quota->state((string)$s['id'],(int)($s['settings']['quota_soft_limit']??1000));
            return $s;
        },$this->sources->all());
    }
    public function find(string $id): ?array { return $this->sources->find($id); }

    public function create(array $input): array
    {
        $provider=strtolower(trim((string)($input['provider']??'')));
        if(!in_array($provider,['manual','wordpress','instagram','youtube','x'],true))throw new RuntimeException('Unsupported provider');
        $settings=is_array($input['settings']??null)?$input['settings']:[]; unset($settings['allow_private_url']);
        $this->validateSettings($provider,$settings);
        return $this->sources->save([
            'id'=>UuidV7::generate(),'provider'=>$provider,'name'=>trim((string)($input['name']??ucfirst($provider))),'handle'=>trim((string)($input['handle']??'')),
            'enabled'=>(bool)($input['enabled']??true),'sync_enabled'=>(bool)($input['sync_enabled']??false),'sync_interval'=>max(60,(int)($input['sync_interval']??300)),
            'connection_mode'=>$input['connection_mode']??null,'settings'=>$settings,'credential_env'=>is_array($input['credential_env']??null)?$input['credential_env']:[],
            'classification_mode'=>$input['classification_mode']??'review','health_status'=>'disabled','last_sync_at'=>null,'last_error'=>null,
        ]);
    }

    public function update(string $id,array $input):array
    {
        $source=$this->find($id); if(!$source)throw new RuntimeException('Source not found');
        foreach(['name','handle','connection_mode','classification_mode'] as $f)if(array_key_exists($f,$input))$source[$f]=$input[$f];
        foreach(['enabled','sync_enabled'] as $f)if(array_key_exists($f,$input))$source[$f]=(bool)$input[$f];
        if(array_key_exists('sync_interval',$input))$source['sync_interval']=max(60,(int)$input['sync_interval']);
        if(isset($input['settings'])&&is_array($input['settings'])){$incoming=$input['settings'];unset($incoming['allow_private_url']);$source['settings']=array_merge($source['settings']??[],$incoming);}
        $this->validateSettings((string)$source['provider'],$source['settings']??[]);
        if(isset($input['credential_env'])&&is_array($input['credential_env']))$source['credential_env']=$input['credential_env'];
        return $this->sources->save($source);
    }

    public function delete(string $id):bool{if(!$this->find($id))return true;if($this->vault)$this->vault->delete($id);return $this->sources->delete($id);}

    public function setCredentials(string $id,array $credentials):array
    {
        if(!$this->find($id))throw new RuntimeException('Source not found'); if(!$this->vault)throw new RuntimeException('Credential vault unavailable'); return $this->vault->put($id,$credentials);
    }

    public function forEngine(array $source):array
    {
        $copy=$source; unset($copy['credential_status'],$copy['quota']); $copy['settings']=is_array($copy['settings']??null)?$copy['settings']:[]; $copy['settings']['allow_private_url']=$this->allowPrivateUrls; $copy['credentials']=[];
        if($this->vault){foreach($this->vault->all((string)$source['id']) as $name=>$value)$copy['credentials'][(string)$name]=$value;}
        foreach(($source['credential_env']??[]) as $name=>$envName){$v=pm_env((string)$envName,null);if($v!==null&&$v!=='')$copy['credentials'][(string)$name]=(string)$v;}
        return $copy;
    }

    public function test(string $id):array
    {
        $source=$this->find($id);if(!$source)throw new RuntimeException('Source not found');
        try{$result=$this->python->post('/v1/source/test',['source'=>$this->forEngine($source)]);$data=$result['data']??[];$this->recordUsage($source,1);$source['health_status']=($data['ok']??false)?(($data['limited']??false)?'limited':'live'):'degraded';$source['last_error']=($data['ok']??false)?null:implode('; ',$data['errors']??['Connection test failed']);$source['last_test_at']=gmdate('c');$this->sources->save($source);return $data;}
        catch(\Throwable $e){$this->errors?->capture('SOURCE_TEST_FAILED',$e->getMessage(),['source_id'=>$id]);throw $e;}
    }

    public function syncPayload(string $id,int $limit=10,?string $cursor=null):array
    {
        $source=$this->find($id);if(!$source)throw new RuntimeException('Source not found');
        if($this->quota){$q=$this->quota->state($id,(int)($source['settings']['quota_soft_limit']??1000));if(($q['ratio']??0)>=1.0)throw new RuntimeException('Source soft quota exhausted for today');}
        $result=$this->python->post('/v1/source/sync',['source'=>$this->forEngine($source),'limit'=>max(1,min(50,$limit)),'cursor'=>$cursor]);$this->recordUsage($source,1);return $result;
    }

    public function markSync(string $id,?string $cursor,?string $error=null):void
    {
        $source=$this->find($id);if(!$source)return;$source['last_sync_at']=gmdate('c');$source['last_cursor']=$cursor;$source['last_error']=$error;
        $q=$this->quota?->state($id,(int)($source['settings']['quota_soft_limit']??1000));
        $source['health_status']=$error?'degraded':(($q&&in_array($q['status'],['quota_low','throttled'],true))?$q['status']:'live');$this->sources->save($source);
        if($error)$this->errors?->capture('SOURCE_SYNC_FAILED',$error,['source_id'=>$id]);
    }

    private function validateSettings(string $provider,array $settings):void
    {
        if($provider==='wordpress'&&!empty($settings['site_url']))UrlGuard::assertPublicHttpUrl((string)$settings['site_url'],$this->allowPrivateUrls);
    }
    private function recordUsage(array $source,int $calls):void{$this->quota?->record((string)$source['id'],$calls,(int)($source['settings']['quota_soft_limit']??1000));}
}
