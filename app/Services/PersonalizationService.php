<?php
namespace PuneMirror\Services;

final class PersonalizationService
{
    public function __construct(private readonly StoryService $stories,private readonly UserStateService $users){}

    public function forYou(int $limit=12):array{return $this->forYouPage($limit)['rows'];}

    public function forYouPage(int $limit=12,?string $cursor=null):array
    {
        $ranked=$this->ranked();
        $offset=0;
        if($cursor){
            foreach($ranked as $i=>$story)if(($story['id']??'')===$cursor){$offset=$i+1;break;}
        }
        $limit=max(1,min(50,$limit));$slice=array_slice($ranked,$offset,$limit+1);
        $hasMore=count($slice)>$limit;if($hasMore)array_pop($slice);
        $next=$hasMore&&$slice?(string)end($slice)['id']:null;
        return ['rows'=>$slice,'has_more'=>$hasMore,'next_cursor'=>$next];
    }

    public function nearYou(int $limit=12):array
    {
        $state=$this->users->state();$areas=array_map([$this,'norm'],(array)($state['user']['preferences']['areas']??[]));
        if(!$areas)return [];
        $rows=[];
        foreach($this->ranked() as $story){
            $locations=array_map(fn($x)=>$this->norm((string)($x['name']??$x['slug']??'')),$story['locations']??[]);
            if(array_intersect($areas,$locations))$rows[]=$story;
            if(count($rows)>=$limit)break;
        }
        return $rows;
    }

    private function ranked():array
    {
        $state=$this->users->state();
        $prefs=(array)($state['user']['preferences']??[]);
        $areas=array_map([$this,'norm'],(array)($prefs['areas']??[]));
        $channels=array_map([$this,'norm'],(array)($prefs['channels']??[]));
        $followed=array_flip($state['follow_story_ids']??[]);
        $bookmarked=array_flip($state['bookmark_story_ids']??[]);
        $rows=$this->stories->feed(50);
        foreach($rows as $i=>&$story){
            $score=max(0,50-$i);$reasons=[];
            $locations=array_map(fn($x)=>$this->norm((string)($x['name']??$x['slug']??'')),$story['locations']??[]);
            $categories=array_map(fn($x)=>$this->norm((string)($x['name']??$x['slug']??'')),$story['categories']??[]);
            if(array_intersect($areas,$locations)){$score+=80;$reasons[]='near_you';}
            if(array_intersect($channels,$categories)){$score+=55;$reasons[]='your_topic';}
            if(isset($followed[$story['id']??''])){$score+=100;$reasons[]='followed';}
            if(isset($bookmarked[$story['id']??''])){$score+=20;$reasons[]='saved_interest';}
            if(($story['type']??'')==='live'){$score+=20;$reasons[]='live';}
            elseif(($story['type']??'')==='developing'){$score+=12;$reasons[]='developing';}
            $story['_personalization']=['score'=>$score,'reasons'=>$reasons];
        }
        unset($story);
        usort($rows,fn($a,$b)=>(int)($b['_personalization']['score']??0)<=>(int)($a['_personalization']['score']??0));
        return $rows;
    }

    private function norm(string $value):string{return strtolower(trim(preg_replace('/\s+/',' ',$value)??$value));}
}
