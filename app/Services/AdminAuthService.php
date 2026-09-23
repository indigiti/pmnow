<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\Session;

final class AdminAuthService
{
    private const PERMISSIONS=[
        'super_admin'=>['*'],
        'admin'=>['admin.view','content.review','content.approve','content.edit','sources.manage','jobs.manage','system.view'],
        'editor'=>['admin.view','content.review','content.approve','content.edit','sources.view','system.view'],
        'reviewer'=>['admin.view','content.review','content.approve','sources.view'],
        'viewer'=>['admin.view','sources.view','system.view'],
    ];
    public function __construct(private readonly JsonStore $store,private readonly array $config){}
    public function user():?array
    {
        Session::start();$id=Session::get('admin_user_id');if($id&&($u=$this->store->get('users',(string)$id))&&($u['role']??'')!=='reader')return $this->publicUser($u);
        if(($this->config['admin']['dev_bypass']??false)===true&&($this->config['env']??'local')!=='production'){foreach($this->store->all('users') as $u)if(in_array(($u['role']??''),['super_admin','admin'],true)){Session::put('admin_user_id',$u['id']);return $this->publicUser($u);}}
        return null;
    }
    public function login(string $email,string $password):bool
    {
        foreach($this->store->all('users') as $u){if(strtolower((string)($u['email']??''))!==strtolower(trim($email)))continue;if(!in_array(($u['role']??''),array_keys(self::PERMISSIONS),true))continue;if(!password_verify($password,(string)($u['password_hash']??'')))return false;Session::put('admin_user_id',$u['id']);Session::regenerate();$u['last_login_at']=gmdate('c');$this->store->put('users',$u);return true;}return false;
    }
    public function logout():void{Session::destroy();}
    public function can(string $permission):bool{$u=$this->user();if(!$u)return false;$a=self::PERMISSIONS[$u['role']??'']??[];return in_array('*',$a,true)||in_array($permission,$a,true);}
    public function require(string $permission='admin.view'):array{$u=$this->user();if(!$u||!$this->can($permission))throw new \RuntimeException('ADMIN_AUTH_REQUIRED');return $u;}
    private function publicUser(array $u):array{unset($u['password_hash']);return $u;}
}
