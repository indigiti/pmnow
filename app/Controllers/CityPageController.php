<?php
namespace PuneMirror\Controllers;
use PuneMirror\Core\Request;use PuneMirror\Core\Response;use PuneMirror\Core\View;use PuneMirror\Services\CommunityService;use PuneMirror\Services\EventService;use PuneMirror\Services\SeoService;use PuneMirror\Services\UserStateService;
final class CityPageController {
 public function __construct(private readonly View $view,private readonly CommunityService $community,private readonly EventService $events,private readonly UserStateService $users,private readonly SeoService $seo){}
 public function community(Request $r):never {$state=$this->users->state();$area=isset($r->query['area'])?(string)$r->query['area']:null;$this->page('pages/community',['posts'=>$this->community->feed($area,40),'selectedArea'=>$area,'areas'=>(array)($state['user']['preferences']['areas']??[]),'types'=>$this->community->types(),'activeNav'=>'community','userState'=>$state,'seo'=>$this->seo->site('Pune Community | Pune Mirror Now','Neighbourhood updates, questions and recommendations from Pune residents.','/community')]);}
 public function events(Request $r):never {$state=$this->users->state();$area=isset($r->query['area'])?(string)$r->query['area']:null;$this->page('pages/events',['events'=>$this->events->upcoming($area,60),'selectedArea'=>$area,'areas'=>(array)($state['user']['preferences']['areas']??[]),'activeNav'=>'events','userState'=>$state,'seo'=>$this->seo->site('Pune Events | Pune Mirror Now','Upcoming events across Pune and your neighbourhoods.','/events')]);}
 private function page(string $view,array $data):never {$content=$this->view->render($view,$data);Response::html($this->view->render('layout',array_merge($data,['content'=>$content])));}
}
