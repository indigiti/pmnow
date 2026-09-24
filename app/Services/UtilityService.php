<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;

final class UtilityService
{
    private const KINDS=['traffic','transit','weather','air','civic','outage','emergency','event'];
    private const SEVERITIES=['info','advisory','major','critical'];

    public function __construct(private readonly JsonStore $store,private readonly NotificationService $notifications){}

    public function entities(?string $kind=null):array
    {
        $rows=$this->store->all('utility-entities');
        if($kind!==null&&$kind!=='')$rows=array_values(array_filter($rows,fn($r)=>($r['kind']??'')===$kind));
        usort($rows,fn($a,$b)=>strcmp((string)($a['name']??''),(string)($b['name']??'')));
        return array_map([$this,'decorateEntity'],$rows);
    }

    public function entity(string $id):?array
    {
        $row=$this->store->get('utility-entities',$id);
        return $row?$this->decorateEntity($row):null;
    }

    public function entityBySlug(string $slug):?array
    {
        foreach($this->store->all('utility-entities') as $row){
            if(strtolower((string)($row['slug']??''))===strtolower(trim($slug)))return $this->decorateEntity($row);
        }
        return null;
    }

    public function entityFeed(string $entityId,bool $activeOnly=false,int $limit=50):array
    {
        $entity=$this->entity($entityId);if(!$entity)return [];
        $rows=[];
        foreach($this->store->all('utility-updates') as $row){
            if(($row['entity_id']??'')!==$entityId||!($row['is_public']??true))continue;
            if($activeOnly&&($row['status']??'active')!=='active')continue;
            $rows[]=$this->decorateUpdate($row,$entity);
        }
        usort($rows,fn($a,$b)=>strcmp((string)($b['published_at']??$b['created_at']??''),(string)($a['published_at']??$a['created_at']??'')));
        return array_slice($rows,0,max(1,min(100,$limit)));
    }

    public function feed(?string $kind=null,?string $area=null,bool $activeOnly=true,int $limit=50):array
    {
        $kind=$kind!==null?strtolower(trim($kind)):null;
        $areaNorm=$area!==null?$this->norm($area):null;
        $rows=[];
        foreach($this->store->all('utility-updates') as $row){
            if(!($row['is_public']??true))continue;
            if($activeOnly&&($row['status']??'active')!=='active')continue;
            $entity=$this->entity((string)($row['entity_id']??''));
            if(!$entity)continue;
            if($kind&&($entity['kind']??'')!==$kind)continue;
            if($areaNorm){
                $areas=array_map([$this,'norm'],array_merge([(string)($row['area']??'')],(array)($entity['areas']??[])));
                if(!in_array($areaNorm,$areas,true)&&!in_array('pune',$areas,true)&&!in_array('citywide',$areas,true))continue;
            }
            $rows[]=$this->decorateUpdate($row,$entity);
        }
        usort($rows,function($a,$b){
            $severity=['critical'=>4,'major'=>3,'advisory'=>2,'info'=>1];
            $sa=$severity[$a['severity']??'info']??1;$sb=$severity[$b['severity']??'info']??1;
            if($sa!==$sb)return $sb<=>$sa;
            return strcmp((string)($b['published_at']??$b['created_at']??''),(string)($a['published_at']??$a['created_at']??''));
        });
        return array_slice($rows,0,max(1,min(100,$limit)));
    }

    public function personalized(array $user,int $limit=30):array
    {
        $areas=array_map([$this,'norm'],(array)($user['preferences']['areas']??[]));
        $rows=$this->feed(null,null,true,100);
        foreach($rows as &$row){
            $row['_utility_score']=0;
            $actual=array_map([$this,'norm'],array_merge([(string)($row['area']??'')],(array)($row['entity']['areas']??[])));
            if($areas&&array_intersect($areas,$actual))$row['_utility_score']+=50;
            if(in_array('pune',$actual,true)||in_array('citywide',$actual,true))$row['_utility_score']+=10;
            $row['_utility_score']+=match($row['severity']??'info'){'critical'=>40,'major'=>30,'advisory'=>20,default=>10};
        }
        unset($row);
        usort($rows,fn($a,$b)=>(int)($b['_utility_score']??0)<=>(int)($a['_utility_score']??0));
        return array_slice($rows,0,$limit);
    }

    public function createEntity(array $data):array
    {
        $kind=strtolower(trim((string)($data['kind']??'')));
        if(!in_array($kind,self::KINDS,true))throw new \RuntimeException('Unsupported utility kind');
        $name=trim((string)($data['name']??''));
        if($name==='')throw new \RuntimeException('Utility entity name required');
        $areas=$this->cleanList((array)($data['areas']??[]),12);
        if(!$areas)$areas=['Pune'];
        $record=[
            'id'=>isset($data['id'])?(string)$data['id']:UuidV7::generate(),
            'kind'=>$kind,'name'=>$name,'slug'=>$this->slug((string)($data['slug']??$name)),
            'authority'=>trim((string)($data['authority']??'')),
            'areas'=>$areas,'status'=>in_array(($data['status']??'active'),['active','inactive'],true)?$data['status']:'active',
            'source_label'=>trim((string)($data['source_label']??'')),
            'source_url'=>$this->safeSourceUrl($data['source_url']??null),
            'external_id'=>isset($data['external_id'])?trim((string)$data['external_id']):null,
            'metadata'=>is_array($data['metadata']??null)?$data['metadata']:[],
        ];
        return $this->store->put('utility-entities',$record);
    }

    public function createUpdate(array $data):array
    {
        $entity=$this->entity((string)($data['entity_id']??''));
        if(!$entity)throw new \RuntimeException('Utility entity not found');
        $severity=strtolower(trim((string)($data['severity']??'info')));
        if(!in_array($severity,self::SEVERITIES,true))throw new \RuntimeException('Unsupported utility severity');
        $title=trim((string)($data['title']??''));
        if($title==='')throw new \RuntimeException('Utility update title required');
        $summary=trim((string)($data['summary']??''));
        $status=in_array(($data['status']??'active'),['active','resolved'],true)?$data['status']:'active';
        $record=$this->store->put('utility-updates',[
            'id'=>isset($data['id'])?(string)$data['id']:UuidV7::generate(),
            'entity_id'=>$entity['id'],'title'=>$title,'summary'=>$summary,
            'severity'=>$severity,'status'=>$status,'area'=>trim((string)($data['area']??($entity['areas'][0]??'Pune'))),
            'starts_at'=>$this->dateOrNull($data['starts_at']??null),
            'ends_at'=>$this->dateOrNull($data['ends_at']??null),
            'published_at'=>$this->dateOrNull($data['published_at']??null)??gmdate('c'),
            'verified_at'=>$this->dateOrNull($data['verified_at']??null)??gmdate('c'),
            'source_label'=>trim((string)($data['source_label']??$entity['source_label']??'')),
            'source_url'=>$this->safeSourceUrl($data['source_url']??$entity['source_url']??null),
            'is_public'=>array_key_exists('is_public',$data)?(bool)$data['is_public']:true,
            'external_id'=>isset($data['external_id'])?trim((string)$data['external_id']):null,
            'metadata'=>is_array($data['metadata']??null)?$data['metadata']:[],
        ]);
        $sent=$this->notifyFollowers($entity,$record);
        $this->store->appendEvent('utility',[
            'event'=>'utility_update_created','entity_id'=>$entity['id'],'update_id'=>$record['id'],
            'kind'=>$entity['kind'],'severity'=>$severity,'follower_notifications'=>$sent
        ]);
        return $this->decorateUpdate($record,$entity)+['follower_notifications'=>$sent];
    }

    public function resolveUpdate(string $id):array
    {
        $row=$this->store->get('utility-updates',$id);
        if(!$row)throw new \RuntimeException('Utility update not found');
        $row['status']='resolved';$row['resolved_at']=gmdate('c');
        $saved=$this->store->put('utility-updates',$row);
        $entity=$this->entity((string)$saved['entity_id']);
        return $this->decorateUpdate($saved,$entity?:[]);
    }

    public function toggleFollow(string $userId,string $entityId):bool
    {
        if(!$this->entity($entityId))throw new \RuntimeException('Utility entity not found');
        foreach($this->store->all('utility-follows') as $row){
            if(($row['user_id']??'')===$userId&&($row['entity_id']??'')===$entityId){
                $this->store->delete('utility-follows',(string)$row['id']);
                return false;
            }
        }
        $this->store->put('utility-follows',['id'=>UuidV7::generate(),'user_id'=>$userId,'entity_id'=>$entityId]);
        return true;
    }

    public function followedEntityIds(string $userId):array
    {
        return array_values(array_map(fn($r)=>(string)$r['entity_id'],array_filter($this->store->all('utility-follows'),fn($r)=>($r['user_id']??'')===$userId)));
    }

    public function metrics():array
    {
        $updates=$this->store->all('utility-updates');
        $active=array_values(array_filter($updates,fn($r)=>($r['status']??'active')==='active'&&($r['is_public']??true)));
        $byKind=[];$critical=0;
        foreach($active as $row){
            $entity=$this->entity((string)($row['entity_id']??''));$kind=(string)($entity['kind']??'unknown');
            $byKind[$kind]=($byKind[$kind]??0)+1;
            if(($row['severity']??'')==='critical')$critical++;
        }
        ksort($byKind);
        return ['entities'=>count($this->store->all('utility-entities')),'active_updates'=>count($active),'critical'=>$critical,'follows'=>count($this->store->all('utility-follows')),'by_kind'=>$byKind];
    }

    public function kinds():array{return self::KINDS;}

    private function notifyFollowers(array $entity,array $update):int
    {
        $count=0;
        foreach($this->store->all('utility-follows') as $follow){
            if(($follow['entity_id']??'')!==($entity['id']??''))continue;
            $row=$this->notifications->notifyUser(
                (string)$follow['user_id'],'utility_update',(string)$update['title'],(string)($update['summary']??''),
                null,'utility-update:'.$update['id'],['utility_entity_id'=>$entity['id'],'utility_update_id'=>$update['id'],'kind'=>$entity['kind']]
            );
            if($row)$count++;
        }
        return $count;
    }

    private function decorateEntity(array $row):array
    {
        $row['path']='/utility/'.$row['slug'];
        return $row;
    }

    private function decorateUpdate(array $row,array $entity):array
    {
        $row['entity']=$entity;
        $row['path']='/utility/'.($entity['slug']??'');
        return $row;
    }

    private function cleanList(array $values,int $limit):array
    {
        $out=[];
        foreach($values as $value){
            $value=trim((string)$value);if($value==='')continue;
            $out[strtolower($value)]=$value;if(count($out)>=$limit)break;
        }
        return array_values($out);
    }

    private function safeSourceUrl(mixed $value):?string
    {
        $value=trim((string)($value??''));if($value==='')return null;
        $parts=parse_url($value);$scheme=strtolower((string)($parts['scheme']??''));
        if(!in_array($scheme,['http','https'],true))throw new \RuntimeException('Utility source URL must be HTTP(S)');
        return $value;
    }

    private function dateOrNull(mixed $value):?string
    {
        $value=trim((string)($value??''));if($value==='')return null;
        $ts=strtotime($value);if(!$ts)throw new \RuntimeException('Invalid utility date');
        return gmdate('c',$ts);
    }

    private function slug(string $value):string
    {
        $value=strtolower(trim($value));$value=preg_replace('/[^a-z0-9]+/','-',$value)??'';return trim($value,'-')?:'utility';
    }

    private function norm(string $value):string{return strtolower(trim(preg_replace('/\s+/',' ',$value)??$value));}
}
