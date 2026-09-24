<?php
namespace PuneMirror\Controllers;

use PuneMirror\Core\Request;
use PuneMirror\Core\Response;
use PuneMirror\Services\AnalyticsService;
use PuneMirror\Services\UtilityService;
use PuneMirror\Services\UserStateService;

final class UtilityApiController
{
    public function __construct(private readonly UtilityService $utility,private readonly UserStateService $users,private readonly AnalyticsService $analytics){}

    public function feed(Request $request):never
    {
        $kind=isset($request->query['kind'])?(string)$request->query['kind']:null;
        $area=isset($request->query['area'])?(string)$request->query['area']:null;
        $limit=max(1,min(100,(int)($request->query['limit']??50)));
        Response::json($this->utility->feed($kind,$area,true,$limit));
    }

    public function entities(Request $request):never
    {
        $kind=isset($request->query['kind'])?(string)$request->query['kind']:null;
        Response::json($this->utility->entities($kind));
    }

    public function toggleFollow(string $entityId):never
    {
        $user=$this->users->currentUser();
        try{
            $active=$this->utility->toggleFollow((string)$user['id'],$entityId);
            $this->analytics->track((string)$user['id'],'utility_follow',['entity_id'=>$entityId,'active'=>$active]);
            Response::json(['entity_id'=>$entityId,'followed'=>$active]);
        }catch(\Throwable $e){Response::error('UTILITY_FOLLOW_FAILED',$e->getMessage(),422);}
    }
}
