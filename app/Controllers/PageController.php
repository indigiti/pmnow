<?php
namespace PuneMirror\Controllers;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\Response;
use PuneMirror\Core\View;
use PuneMirror\Services\NotificationService;
use PuneMirror\Services\PersonalizationService;
use PuneMirror\Services\SeoService;
use PuneMirror\Services\StoryService;
use PuneMirror\Services\UserStateService;
use PuneMirror\Services\UtilityService;
use PuneMirror\Core\Request;

final class PageController
{
    public function __construct(
        private readonly View $view,
        private readonly StoryService $stories,
        private readonly UserStateService $users,
        private readonly NotificationService $notifications,
        private readonly JsonStore $store,
        private readonly SeoService $seo,
        private readonly PersonalizationService $personalization,
        private readonly ?UtilityService $utility=null
    ) {}

    public function home(): never
    {
        $state=$this->users->state();
        $this->page('pages/home',[
            'stories'=>$this->personalization->forYou(12),
            'userState'=>$state,
            'activeNav'=>'home',
            'seo'=>$this->seo->site(),
        ]);
    }

    public function nearYou(): never
    {
        $state=$this->users->state();
        $areas=(array)($state['user']['preferences']['areas']??[]);
        $this->page('pages/near-you',[
            'stories'=>$this->personalization->nearYou(20),
            'areas'=>$areas,
            'activeNav'=>'home',
            'userState'=>$state,
            'seo'=>$this->seo->privatePage('Near You | Pune Mirror Now','/near-you'),
        ]);
    }

    public function explore(): never
    {
        $this->page('pages/explore',[
            'stories'=>$this->stories->feed(12),
            'activeNav'=>'explore',
            'userState'=>$this->users->state(),
            'seo'=>$this->seo->site('Explore Pune | Pune Mirror Now','Search Pune news by neighbourhood, topic and story.','/explore'),
        ]);
    }

    public function utility(Request $request):never
    {
        if(!$this->utility)Response::html('<h1>Utility unavailable</h1>',503);
        $state=$this->users->state();
        $kind=isset($request->query['kind'])?(string)$request->query['kind']:null;
        $area=isset($request->query['area'])?(string)$request->query['area']:null;
        $updates=($kind||$area)?$this->utility->feed($kind,$area,true,40):$this->utility->personalized((array)$state['user'],40);
        $this->page('pages/utility',[
            'updates'=>$updates,
            'entities'=>$this->utility->entities($kind),
            'followedEntityIds'=>$this->utility->followedEntityIds((string)$state['user']['id']),
            'kinds'=>$this->utility->kinds(),
            'selectedKind'=>$kind,
            'selectedArea'=>$area,
            'activeNav'=>'utility',
            'userState'=>$state,
            'seo'=>$this->seo->site('Pune Utility | Pune Mirror Now','Traffic, transit, weather, civic, outage and emergency updates for Pune.','/utility'),
        ]);
    }

    public function utilityEntity(string $slug):never
    {
        if(!$this->utility)Response::html('<h1>Utility unavailable</h1>',503);
        $entity=$this->utility->entityBySlug($slug);
        if(!$entity)Response::html('<h1>Utility entity not found</h1>',404);
        $state=$this->users->state();
        $this->page('pages/utility-entity',[
            'entity'=>$entity,
            'updates'=>$this->utility->entityFeed((string)$entity['id'],false,50),
            'isFollowed'=>in_array((string)$entity['id'],$this->utility->followedEntityIds((string)$state['user']['id']),true),
            'activeNav'=>'utility',
            'userState'=>$state,
            'seo'=>$this->seo->site((string)$entity['name'].' | Pune Utility','Verified utility updates for '.(string)$entity['name'].'.',(string)$entity['path']),
        ]);
    }

    public function story(string $id): never { $this->renderStory($id); }
    public function gallery(string $id): never { $this->renderStory($id); }
    public function developing(string $id): never { $this->renderStory($id); }
    public function live(string $id): never { $this->renderStory($id); }

    public function canonicalStory(string $slug): never
    {
        $pos=strrpos($slug,'--');
        if($pos===false)Response::html('<h1>Story not found</h1>',404);
        $this->renderStory(substr($slug,$pos+2));
    }

    public function topic(string $kind,string $slug): never
    {
        $collection=$kind==='area'?'locations':'categories';
        $match=null;
        foreach($this->store->all($collection) as $row){
            if(strtolower((string)($row['slug']??''))===strtolower($slug)){$match=$row;break;}
        }
        if(!$match)Response::html('<h1>Topic not found</h1>',404);
        $stories=$this->stories->taxonomyFeed($kind,$slug,30);
        $this->page('pages/topic',[
            'topic'=>$match,
            'topicKind'=>$kind,
            'stories'=>$stories,
            'activeNav'=>'explore',
            'userState'=>$this->users->state(),
            'seo'=>$this->seo->topic($kind,(string)($match['name']??$slug),$slug),
        ]);
    }

    public function watch(): never
    {
        $reels=$this->stories->byType('reel',10);
        if(!$reels)$reels=array_slice($this->stories->feed(10),0,5);
        $this->page('pages/watch',[
            'reels'=>$reels,
            'activeNav'=>'watch',
            'userState'=>$this->users->state(),
            'seo'=>$this->seo->site('Watch Pune | Pune Mirror Now','Visual Pune news and quick local updates.','/watch'),
        ]);
    }

    public function notifications(): never
    {
        $state=$this->users->state();
        $rows=$this->notifications->forUser((string)$state['user']['id']);
        $this->page('pages/notifications',[
            'notifications'=>$rows,
            'activeNav'=>'notifications',
            'userState'=>$state,
            'seo'=>$this->seo->privatePage('Your alerts | Pune Mirror Now','/notifications'),
        ]);
    }

    public function profile(): never
    {
        $state=$this->users->state();$saved=[];
        foreach($state['bookmark_story_ids'] as $id)if($story=$this->stories->find((string)$id))$saved[]=$story;
        $areas=$this->store->all('locations');$channels=$this->store->all('categories');
        usort($areas,fn($a,$b)=>strcmp((string)($a['name']??''),(string)($b['name']??'')));
        usort($channels,fn($a,$b)=>strcmp((string)($a['name']??''),(string)($b['name']??'')));
        $this->page('pages/profile',[
            'state'=>$state,
            'saved'=>$saved,
            'areas'=>$areas,
            'channels'=>$channels,
            'activeNav'=>'profile',
            'userState'=>$state,
            'seo'=>$this->seo->privatePage('My Pune | Pune Mirror Now','/profile'),
        ]);
    }

    private function renderStory(string $id): never
    {
        $story=$this->stories->find($id);
        if(!$story)Response::html('<h1>Story not found</h1>',404);
        $data=['story'=>$story,'activeNav'=>'','userState'=>$this->users->state(),'seo'=>$this->seo->story($story)];
        $view=match($story['type']??'article'){
            'gallery'=>'pages/gallery',
            'developing'=>'pages/developing',
            'live'=>'pages/live',
            default=>'pages/story',
        };
        if(($story['type']??'')==='live')$data['live']=$this->stories->liveStoryByStory($id);
        $this->page($view,$data);
    }

    private function page(string $contentView,array $data):never
    {
        $state=$data['userState']??$this->users->state();
        $unread=0;foreach($this->notifications->forUser((string)$state['user']['id']) as $n)if(empty($n['read_at']))$unread++;
        $data['userState']=$state;$data['unreadNotifications']=$unread;
        $data['seo']??=$this->seo->site();
        $content=$this->view->render($contentView,$data);
        $html=$this->view->render('layout',$data+['content'=>$content]);
        Response::html($html);
    }
}
