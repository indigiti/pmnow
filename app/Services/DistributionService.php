<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;

final class DistributionService
{
    public function __construct(
        private readonly JsonStore $store,
        private readonly StoryService $stories,
        private readonly NotificationService $notifications,
        private readonly ?AnalyticsService $analytics=null
    ){}

    public function alertStory(string $storyId,string $kind='breaking',?string $title=null,?string $body=null):array
    {
        $story=$this->stories->find($storyId);
        if(!$story)throw new \RuntimeException('Story not found');
        $kind=strtolower(trim($kind));
        if(!in_array($kind,['breaking','neighbourhood','topic'],true))throw new \RuntimeException('Unsupported alert kind');

        $sent=0;$matched=0;
        foreach($this->store->all('users') as $user){
            if(($user['role']??'reader')!=='reader')continue;
            $prefs=$this->notificationPreferences($user);
            if(!($prefs['enabled']??true))continue;
            if(!$this->eligible($user,$story,$kind,$prefs))continue;
            $matched++;
            $key='story-alert:'.$kind.':'.$storyId;
            $row=$this->notifications->notifyUser(
                (string)$user['id'],
                $kind.'_alert',
                $title?:((string)($story['headline']??'Pune update')),
                $body?:((string)($story['deck']??$story['summary']??'')),
                $storyId,
                $key,
                ['kind'=>$kind,'path'=>$story['path']??pm_story_path($story)]
            );
            if($row)$sent++;
        }
        $this->store->appendEvent('distribution',[
            'event'=>'story_alert','story_id'=>$storyId,'kind'=>$kind,'matched'=>$matched,'sent'=>$sent
        ]);
        return ['story_id'=>$storyId,'kind'=>$kind,'matched'=>$matched,'sent'=>$sent];
    }

    public function deliverDigest(string $userId,string $period):array
    {
        $user=$this->store->get('users',$userId);
        if(!$user)throw new \RuntimeException('User not found');
        $period=strtolower(trim($period));
        if(!in_array($period,['morning','evening','weekend'],true))throw new \RuntimeException('Unsupported digest period');
        $prefs=$this->notificationPreferences($user);
        $flag=$period.'_digest';
        if(!($prefs['enabled']??true)||!($prefs[$flag]??false))return ['delivered'=>false,'reason'=>'disabled','period'=>$period];

        $stories=$this->rankForUser($user,6);
        if(!$stories)return ['delivered'=>false,'reason'=>'empty','period'=>$period];
        $date=$this->localDate($prefs);
        $key='digest:'.$period.':'.$date;
        $title=match($period){'morning'=>'Morning Pune','evening'=>'Evening Pune',default=>'Weekend Pune'};
        $body=implode(' · ',array_map(fn($s)=>(string)($s['headline']??'Pune update'),array_slice($stories,0,3)));
        $row=$this->notifications->notifyUser($userId,'digest_'.$period,$title,$body,null,$key,[
            'period'=>$period,'story_ids'=>array_values(array_column($stories,'id'))
        ]);
        if(!$row)return ['delivered'=>false,'reason'=>'duplicate','period'=>$period];

        $user['distribution_state']['last_'.$period.'_digest_date']=$date;
        $this->store->put('users',$user);
        $this->store->appendEvent('distribution',['event'=>'digest_delivered','user_id'=>$userId,'period'=>$period,'story_count'=>count($stories)]);
        $this->analytics?->track($userId,'digest_delivered',['period'=>$period,'story_count'=>count($stories)]);
        return ['delivered'=>true,'period'=>$period,'notification_id'=>$row['id'],'story_count'=>count($stories)];
    }

    public function channelFeed(int $limit=20):array
    {
        return array_map(fn($story)=>[
            'id'=>$story['id']??null,
            'headline'=>$story['headline']??'Pune update',
            'summary'=>$story['deck']??$story['summary']??'',
            'url'=>pm_absolute_url($story['path']??pm_story_path($story)),
            'image'=>pm_absolute_url(pm_media($story['media'][0]??null)),
            'published_at'=>$story['published_at']??$story['created_at']??null,
            'areas'=>array_values(array_filter(array_column($story['locations']??[],'name'))),
            'topics'=>array_values(array_filter(array_column($story['categories']??[],'name'))),
        ],$this->stories->feed(max(1,min(50,$limit))));
    }

    public function notificationPreferences(array $user):array
    {
        return array_merge([
            'enabled'=>true,'breaking'=>true,'following'=>true,
            'area_alerts'=>true,'topic_alerts'=>true,
            'morning_digest'=>false,'evening_digest'=>false,'weekend_digest'=>false,
            'timezone'=>'Asia/Kolkata',
        ],(array)($user['notification_preferences']??[]));
    }

    private function eligible(array $user,array $story,string $kind,array $prefs):bool
    {
        if($kind==='breaking')return (bool)($prefs['breaking']??true);
        $content=(array)($user['preferences']??[]);
        if($kind==='neighbourhood'&&!($prefs['area_alerts']??true))return false;
        if($kind==='topic'&&!($prefs['topic_alerts']??true))return false;
        $wanted=$kind==='neighbourhood'?(array)($content['areas']??[]):(array)($content['channels']??[]);
        $actual=$kind==='neighbourhood'
            ? array_column($story['locations']??[],'name')
            : array_column($story['categories']??[],'name');
        $wanted=array_map([$this,'norm'],$wanted);$actual=array_map([$this,'norm'],$actual);
        return (bool)array_intersect($wanted,$actual);
    }

    private function rankForUser(array $user,int $limit):array
    {
        $content=(array)($user['preferences']??[]);
        $areas=array_map([$this,'norm'],(array)($content['areas']??[]));
        $topics=array_map([$this,'norm'],(array)($content['channels']??[]));
        $rows=$this->stories->feed(50);
        foreach($rows as $i=>&$story){
            $score=50-$i;
            $storyAreas=array_map([$this,'norm'],array_column($story['locations']??[],'name'));
            $storyTopics=array_map([$this,'norm'],array_column($story['categories']??[],'name'));
            if(array_intersect($areas,$storyAreas))$score+=60;
            if(array_intersect($topics,$storyTopics))$score+=40;
            if(($story['type']??'')==='live')$score+=20;
            $story['_distribution_score']=$score;
        }
        unset($story);
        usort($rows,fn($a,$b)=>(int)($b['_distribution_score']??0)<=>(int)($a['_distribution_score']??0));
        return array_slice($rows,0,$limit);
    }

    private function localDate(array $prefs):string
    {
        try{$tz=new \DateTimeZone((string)($prefs['timezone']??'Asia/Kolkata'));}catch(\Throwable){$tz=new \DateTimeZone('Asia/Kolkata');}
        return (new \DateTimeImmutable('now',$tz))->format('Y-m-d');
    }

    private function norm(string $value):string{return strtolower(trim(preg_replace('/\s+/',' ',$value)??$value));}
}
