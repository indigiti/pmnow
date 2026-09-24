<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use RuntimeException;

final class AnalyticsService
{
    private const EVENTS=[
        'page_view','story_impression','story_open','story_read_25','story_read_50','story_read_90',
        'share','bookmark','follow','search','notification_open','reel_view','gallery_complete','preference_update','near_you_open'
    ];

    public function __construct(private readonly JsonStore $store) {}

    public function newsroomSummary():array
    {
        $now=time();$currentStart=$now-(7*86400);$previousStart=$now-(14*86400);
        $currentUsers=[];$previousUsers=[];$counts=[];$currentEvents=0;
        $pattern=dirname($this->store->rootPath()).'/events/analytics-*.jsonl';
        foreach(glob($pattern)?:[] as $file){
            $fh=@fopen($file,'rb');if(!$fh)continue;
            try{
                while(($line=fgets($fh))!==false){
                    $row=json_decode($line,true);if(!is_array($row))continue;
                    $ts=strtotime((string)($row['occurred_at']??$row['at']??''));if(!$ts||$ts<$previousStart)continue;
                    $uid=(string)($row['user_id']??'');$event=(string)($row['event']??'');
                    if($ts>=$currentStart){
                        $currentEvents++;$counts[$event]=($counts[$event]??0)+1;
                        if($uid!=='')$currentUsers[$uid]=true;
                    }elseif($uid!=='')$previousUsers[$uid]=true;
                }
            }finally{fclose($fh);}
        }
        $returning=count(array_intersect_key($currentUsers,$previousUsers));
        $personalized=0;$areas=[];$topics=[];
        foreach($this->store->all('users') as $user){
            $prefs=(array)($user['preferences']??[]);
            $userAreas=array_values(array_filter(array_map('strval',(array)($prefs['areas']??[]))));
            $userTopics=array_values(array_filter(array_map('strval',(array)($prefs['channels']??[]))));
            if($userAreas||$userTopics)$personalized++;
            foreach($userAreas as $name)$areas[$name]=($areas[$name]??0)+1;
            foreach($userTopics as $name)$topics[$name]=($topics[$name]??0)+1;
        }
        arsort($areas);arsort($topics);
        return [
            'window_days'=>7,
            'events'=>$currentEvents,
            'engaged_readers'=>count($currentUsers),
            'returning_readers'=>$returning,
            'personalized_readers'=>$personalized,
            'event_counts'=>$counts,
            'top_areas'=>array_slice($areas,0,5,true),
            'top_topics'=>array_slice($topics,0,5,true),
        ];
    }

    public function track(string $userId,string $event,array $properties=[]):array
    {
        if(!in_array($event,self::EVENTS,true))throw new RuntimeException('Unsupported analytics event');
        $clean=[];
        foreach(array_slice($properties,0,20,true) as $key=>$value){
            $key=preg_replace('/[^a-z0-9_.-]/i','',(string)$key)??'';
            if($key==='')continue;
            if(is_bool($value)||is_int($value)||is_float($value)||$value===null)$clean[$key]=$value;
            elseif(is_string($value))$clean[$key]=mb_substr($value,0,240);
        }
        $row=['event'=>$event,'user_id'=>$userId,'properties'=>$clean,'occurred_at'=>gmdate('c')];
        $this->store->appendEvent('analytics',$row);
        return $row;
    }
}
