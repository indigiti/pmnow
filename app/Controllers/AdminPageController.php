<?php
namespace PuneMirror\Controllers;

use PuneMirror\Core\Response;
use PuneMirror\Core\View;
use PuneMirror\Services\AdminAuthService;
use PuneMirror\Services\EditorialService;
use PuneMirror\Services\SourceService;
use PuneMirror\Services\ErrorCenterService;
use PuneMirror\Services\SystemHealthService;
use PuneMirror\Services\AnalyticsService;
use PuneMirror\Services\UtilityService;
use PuneMirror\Contracts\JobRepository;
use PuneMirror\Core\JsonStore;

final class AdminPageController
{
    public function __construct(private readonly View $view,private readonly AdminAuthService $auth,private readonly EditorialService $editorial,private readonly SourceService $sources,private readonly JobRepository $jobs,private readonly JsonStore $store,private readonly ?ErrorCenterService $errors=null,private readonly ?SystemHealthService $health=null,private readonly ?AnalyticsService $analytics=null,private readonly ?UtilityService $utility=null){}
    public function login(?string $error=null):never{Response::html($this->view->render('admin/login',['error'=>$error]));}
    public function dashboard():never{$u=$this->requireUser();$this->page('admin/dashboard',['adminUser'=>$u,'metrics'=>$this->editorial->metrics(),'sources'=>$this->sources->all(),'jobs'=>array_slice($this->jobs->all(),0,8),'readerAnalytics'=>$this->analytics?->newsroomSummary()??[]]);}
    public function inbox():never{$u=$this->requireUser();$this->page('admin/inbox',['adminUser'=>$u,'items'=>$this->editorial->inbox(),'metrics'=>$this->editorial->metrics()]);}
    public function sources():never{$u=$this->requireUser();$this->page('admin/sources',['adminUser'=>$u,'sources'=>$this->sources->all()]);}
    public function jobs():never{$u=$this->requireUser();$this->page('admin/jobs',['adminUser'=>$u,'jobs'=>$this->jobs->all()]);}
    public function distribution():never{$u=$this->requireUser();$rows=array_values(array_filter($this->store->all('stories'),fn($s)=>($s['status']??'')==='published'));usort($rows,fn($a,$b)=>strcmp((string)($b['published_at']??$b['created_at']??''),(string)($a['published_at']??$a['created_at']??'')));$this->page('admin/distribution',['adminUser'=>$u,'stories'=>array_slice($rows,0,30)]);}
    public function utility():never
    {
        $u=$this->requireUser();
        $this->page('admin/utility',[
            'adminUser'=>$u,
            'utilityMetrics'=>$this->utility?->metrics()??[],
            'utilityEntities'=>$this->utility?->entities()??[],
            'utilityUpdates'=>$this->utility?->feed(null,null,false,100)??[],
            'utilityKinds'=>$this->utility?->kinds()??[],
        ]);
    }
    public function notifications():never{$u=$this->requireUser();$rows=$this->store->all('notifications');usort($rows,fn($a,$b)=>strcmp((string)($b['created_at']??''),(string)($a['created_at']??'')));$this->page('admin/notifications',['adminUser'=>$u,'notifications'=>$rows]);}
    public function system():never{$u=$this->requireUser();$this->page('admin/system',['adminUser'=>$u,'health'=>$this->health?->report()??[],'sources'=>$this->sources->all()]);}
    public function errors():never{$u=$this->requireUser();$this->page('admin/errors',['adminUser'=>$u,'errors'=>$this->errors?->all()??[]]);}
    private function requireUser():array{try{return $this->auth->require('admin.view');}catch(\Throwable){header('Location: '.pm_url('/admin/login'));exit;}}
    private function page(string $contentView,array $data):never{$content=$this->view->render($contentView,$data);Response::html($this->view->render('admin/layout',$data+['content'=>$content]));}
}
