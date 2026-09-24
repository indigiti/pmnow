<?php
namespace PuneMirror\Controllers;

use PuneMirror\Contracts\JobRepository;
use PuneMirror\Core\Request;
use PuneMirror\Core\Response;
use PuneMirror\Services\AdminAuthService;
use PuneMirror\Services\EditorialService;
use PuneMirror\Services\NotificationService;
use PuneMirror\Services\SchedulerService;
use PuneMirror\Services\SearchIndexService;
use PuneMirror\Services\UserStateService;
use PuneMirror\Services\WorkerService;
use PuneMirror\Services\ErrorCenterService;
use PuneMirror\Services\SystemHealthService;
use PuneMirror\Services\DistributionService;
use PuneMirror\Services\UtilityService;

final class NewsroomApiController
{
    public function __construct(
        private readonly AdminAuthService $auth,private readonly EditorialService $editorial,private readonly JobRepository $jobs,private readonly SchedulerService $scheduler,private readonly WorkerService $worker,private readonly SearchIndexService $search,private readonly NotificationService $notifications,private readonly UserStateService $users,
        private readonly ?ErrorCenterService $errors=null,private readonly ?SystemHealthService $health=null,private readonly ?DistributionService $distribution=null,private readonly ?UtilityService $utility=null
    ){}
    public function login(Request $r):never{$ok=$this->auth->login((string)($r->body['email']??''),(string)($r->body['password']??''));if(!$ok)Response::error('LOGIN_FAILED','Invalid credentials',401);Response::json($this->auth->user());}
    public function logout():never{$this->auth->logout();Response::json(['logged_out'=>true]);}
    public function me():never{Response::json($this->auth->user());}
    public function dashboard():never{$this->guard('admin.view');Response::json(['metrics'=>$this->editorial->metrics(),'jobs'=>array_slice($this->jobs->all(),0,20)]);}
    public function inbox(Request $r):never{$this->guard('content.review');Response::json($this->editorial->inbox(isset($r->query['status'])?(string)$r->query['status']:null));}
    public function act(Request $r,string $id):never{$a=$this->guard('content.approve');try{Response::json($this->editorial->act($id,(string)($r->body['action']??''),$a,(array)($r->body['changes']??[]),isset($r->body['reason'])?(string)$r->body['reason']:null));}catch(\Throwable $e){$this->errors?->capture('EDITORIAL_ACTION_FAILED',$e->getMessage(),['content_id'=>$id]);Response::error('EDITORIAL_ACTION_FAILED',$e->getMessage(),422);}}
    public function bulk(Request $r):never{$a=$this->guard('content.approve');Response::json($this->editorial->bulk((array)($r->body['content_ids']??[]),(string)($r->body['action']??''),$a,(array)($r->body['changes']??[])));}
    public function jobs():never{$this->guard('jobs.manage');Response::json($this->jobs->all());}
    public function schedulerTick():never{$this->guard('jobs.manage');Response::json($this->scheduler->tick());}
    public function workerRun():never{$this->guard('jobs.manage');Response::json($this->worker->runNext());}
    public function rebuildSearch():never{$this->guard('system.view');Response::json($this->search->rebuild());}
    public function systemHealth():never{$this->guard('system.view');Response::json($this->health?->report()??['status'=>'unknown']);}
    public function errors():never{$this->guard('system.view');Response::json($this->errors?->all()??[]);}
    public function resolveError(string $id):never{$this->guard('system.view');$r=$this->errors?->resolve($id);if(!$r)Response::error('ERROR_NOT_FOUND','Error record not found',404);Response::json($r);}
    public function distributeAlert(Request $r):never
    {
        $this->guard('distribution.manage');
        if(!$this->distribution)Response::error('DISTRIBUTION_UNAVAILABLE','Distribution service unavailable',503);
        try{
            Response::json($this->distribution->alertStory(
                (string)($r->body['story_id']??''),
                (string)($r->body['kind']??'breaking'),
                isset($r->body['title'])?(string)$r->body['title']:null,
                isset($r->body['body'])?(string)$r->body['body']:null
            ));
        }catch(\Throwable $e){Response::error('DISTRIBUTION_FAILED',$e->getMessage(),422);}
    }

    public function createUtilityEntity(Request $r):never
    {
        $this->guard('utility.manage');
        if(!$this->utility)Response::error('UTILITY_UNAVAILABLE','Utility service unavailable',503);
        try{Response::json($this->utility->createEntity((array)$r->body),201);}
        catch(\Throwable $e){Response::error('UTILITY_ENTITY_INVALID',$e->getMessage(),422);}
    }
    public function createUtilityUpdate(Request $r):never
    {
        $this->guard('utility.manage');
        if(!$this->utility)Response::error('UTILITY_UNAVAILABLE','Utility service unavailable',503);
        try{Response::json($this->utility->createUpdate((array)$r->body),201);}
        catch(\Throwable $e){Response::error('UTILITY_UPDATE_INVALID',$e->getMessage(),422);}
    }
    public function resolveUtilityUpdate(string $id):never
    {
        $this->guard('utility.manage');
        if(!$this->utility)Response::error('UTILITY_UNAVAILABLE','Utility service unavailable',503);
        try{Response::json($this->utility->resolveUpdate($id));}
        catch(\Throwable $e){Response::error('UTILITY_UPDATE_RESOLVE_FAILED',$e->getMessage(),422);}
    }
    public function notifications():never{$u=$this->users->currentUser();Response::json($this->notifications->forUser((string)$u['id']));}
    public function markNotificationRead(string $id):never{$u=$this->users->currentUser();$r=$this->notifications->markRead((string)$u['id'],$id);if(!$r)Response::error('NOTIFICATION_NOT_FOUND','Notification not found',404);Response::json($r);}
    private function guard(string $p):array{try{return $this->auth->require($p);}catch(\Throwable){Response::error('ADMIN_AUTH_REQUIRED','Admin authentication or permission required',401);}}
}
