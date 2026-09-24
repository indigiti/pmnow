<?php
namespace PuneMirror\Controllers;
use PuneMirror\Core\Request;use PuneMirror\Core\Response;use PuneMirror\Services\AdminAuthService;use PuneMirror\Services\CommunityService;use PuneMirror\Services\EventService;
final class CityAdminApiController {
 public function __construct(private readonly AdminAuthService $auth,private readonly CommunityService $community,private readonly EventService $events){}
 public function communityQueue():never {$this->guard();Response::json($this->community->moderationQueue());}
 public function moderate(Request $r,string $id):never {$u=$this->guard();try{Response::json($this->community->moderate($id,(string)($r->body['action']??''),(string)$u['id'],isset($r->body['reason'])?(string)$r->body['reason']:null));}catch(\Throwable $e){Response::error('COMMUNITY_MODERATION_FAILED',$e->getMessage(),422);}}
 public function createEvent(Request $r):never {$this->guard();try{Response::json($this->events->create((array)$r->body),201);}catch(\Throwable $e){Response::error('EVENT_INVALID',$e->getMessage(),422);}}
 private function guard():array {try{return $this->auth->require('content.approve');}catch(\Throwable){Response::error('ADMIN_AUTH_REQUIRED','Admin authentication or permission required',401);}}
}
