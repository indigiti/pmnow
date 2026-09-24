<?php
namespace PuneMirror\Controllers;

use PuneMirror\Core\Request;
use PuneMirror\Core\Response;
use PuneMirror\Services\AnalyticsService;
use PuneMirror\Services\CommunityService;
use PuneMirror\Services\EventService;
use PuneMirror\Services\UserStateService;

final class CityApiController
{
    public function __construct(private readonly CommunityService $community,private readonly EventService $events,private readonly UserStateService $users,private readonly ?AnalyticsService $analytics=null){}

    public function community(Request $r):never{Response::json($this->community->feed(isset($r->query['area'])?(string)$r->query['area']:null,(int)($r->query['limit']??30)));}
    public function submitCommunity(Request $r):never
    {
        try{$u=$this->users->currentUser();$row=$this->community->submit((string)$u['id'],(array)$r->body);$this->analytics?->track('community_submit',['post_id'=>$row['id'],'area'=>$row['area']]);Response::json($row,201);}
        catch(\Throwable $e){Response::error('COMMUNITY_POST_INVALID',$e->getMessage(),422);}
    }
    public function reportCommunity(Request $r,string $id):never
    {
        try{$u=$this->users->currentUser();Response::json($this->community->report((string)$u['id'],$id,(string)($r->body['reason']??'')),201);}
        catch(\Throwable $e){Response::error('COMMUNITY_REPORT_INVALID',$e->getMessage(),422);}
    }
    public function events(Request $r):never{Response::json($this->events->upcoming(isset($r->query['area'])?(string)$r->query['area']:null,(int)($r->query['limit']??40)));}
}
