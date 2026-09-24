<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;

final class CommunityService
{
    private const TYPES=['update','question','recommendation','alert'];
    private const STATUSES=['pending','published','rejected','hidden'];

    public function __construct(private readonly JsonStore $store){}

    public function feed(?string $area=null,int $limit=30):array
    {
        $area=$area!==null?$this->norm($area):null;$rows=[];
        foreach($this->store->all('community-posts') as $row){
            if(($row['status']??'pending')!=='published')continue;
            if($area!==null&&$this->norm((string)($row['area']??''))!==$area)continue;
            $rows[]=$row;
        }
        usort($rows,fn($a,$b)=>strcmp((string)($b['published_at']??$b['created_at']??''),(string)($a['published_at']??$a['created_at']??'')));
        return array_slice($rows,0,max(1,min(100,$limit)));
    }

    public function submit(string $userId,array $data):array
    {
        $type=strtolower(trim((string)($data['type']??'update')));
        if(!in_array($type,self::TYPES,true))throw new \RuntimeException('Unsupported community post type');
        $title=trim((string)($data['title']??''));$body=trim((string)($data['body']??''));$area=trim((string)($data['area']??''));
        if($title===''||$body===''||$area==='')throw new \RuntimeException('Title, body and area are required');
        if(mb_strlen($title)>140||mb_strlen($body)>3000)throw new \RuntimeException('Community post is too long');
        return $this->store->put('community-posts',[
            'id'=>UuidV7::generate(),'user_id'=>$userId,'type'=>$type,'title'=>$title,'body'=>$body,'area'=>$area,
            'status'=>'pending','created_at'=>gmdate('c'),'moderation'=>(object)[]
        ]);
    }

    public function moderate(string $id,string $action,string $moderatorId,?string $reason=null):array
    {
        $row=$this->store->get('community-posts',$id);if(!$row)throw new \RuntimeException('Community post not found');
        $map=['publish'=>'published','reject'=>'rejected','hide'=>'hidden'];
        if(!isset($map[$action]))throw new \RuntimeException('Unsupported moderation action');
        $row['status']=$map[$action];$row['moderation']=['moderator_id'=>$moderatorId,'action'=>$action,'reason'=>trim((string)$reason),'at'=>gmdate('c')];
        if($row['status']==='published'&&!isset($row['published_at']))$row['published_at']=gmdate('c');
        return $this->store->put('community-posts',$row);
    }

    public function report(string $userId,string $postId,string $reason):array
    {
        if(!$this->store->get('community-posts',$postId))throw new \RuntimeException('Community post not found');
        $reason=trim($reason);if($reason==='')throw new \RuntimeException('Report reason required');
        return $this->store->put('community-reports',['id'=>UuidV7::generate(),'post_id'=>$postId,'user_id'=>$userId,'reason'=>mb_substr($reason,0,500),'status'=>'open','created_at'=>gmdate('c')]);
    }

    public function moderationQueue():array
    {
        $rows=array_values(array_filter($this->store->all('community-posts'),fn($r)=>($r['status']??'pending')==='pending'));
        usort($rows,fn($a,$b)=>strcmp((string)($b['created_at']??''),(string)($a['created_at']??'')));
        return $rows;
    }

    public function reports():array
    {
        $rows=$this->store->all('community-reports');
        usort($rows,fn($a,$b)=>strcmp((string)($b['created_at']??''),(string)($a['created_at']??'')));
        return $rows;
    }

    public function types():array{return self::TYPES;}
    private function norm(string $v):string{return strtolower(trim(preg_replace('/\s+/',' ',$v)??$v));}
}
