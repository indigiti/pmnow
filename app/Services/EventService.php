<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;

final class EventService
{
    public function __construct(private readonly JsonStore $store){}

    public function upcoming(?string $area=null,int $limit=40):array
    {
        $now=time();$rows=[];
        foreach($this->store->all('events') as $row){
            if(!($row['is_public']??false))continue;
            $end=strtotime((string)($row['ends_at']??$row['starts_at']??''));if(!$end||$end<$now)continue;
            if($area!==null&&strcasecmp(trim((string)($row['area']??'')),trim($area))!==0)continue;
            $rows[]=$row;
        }
        usort($rows,fn($a,$b)=>strcmp((string)$a['starts_at'],(string)$b['starts_at']));
        return array_slice($rows,0,max(1,min(100,$limit)));
    }

    public function create(array $data):array
    {
        $title=trim((string)($data['title']??''));$venue=trim((string)($data['venue']??''));$starts=$this->date($data['starts_at']??null);
        if($title===''||$venue===''||$starts===null)throw new \RuntimeException('Event title, venue and start time are required');
        $ends=$this->date($data['ends_at']??null);if($ends!==null&&strtotime($ends)<strtotime($starts))throw new \RuntimeException('Event end must be after start');
        return $this->store->put('events',[
            'id'=>UuidV7::generate(),'title'=>$title,'summary'=>trim((string)($data['summary']??'')),'venue'=>$venue,'area'=>trim((string)($data['area']??'Pune')),
            'starts_at'=>$starts,'ends_at'=>$ends,'category'=>trim((string)($data['category']??'community')),
            'source_label'=>trim((string)($data['source_label']??'')),'source_url'=>$this->safeUrl($data['source_url']??null),
            'is_public'=>(bool)($data['is_public']??false),'created_at'=>gmdate('c')
        ]);
    }

    private function date(mixed $v):?string{$v=trim((string)($v??''));if($v==='')return null;$t=strtotime($v);if(!$t)throw new \RuntimeException('Invalid event date');return gmdate('c',$t);}
    private function safeUrl(mixed $v):?string{$v=trim((string)($v??''));if($v==='')return null;$p=parse_url($v);if(!in_array(strtolower((string)($p['scheme']??'')),['http','https'],true))throw new \RuntimeException('Event source URL must be HTTP(S)');return $v;}
}
