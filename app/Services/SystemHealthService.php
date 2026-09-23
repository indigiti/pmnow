<?php
namespace PuneMirror\Services;

use PuneMirror\Core\PythonClient;
use PuneMirror\Runtime\RedisClient;

final class SystemHealthService
{
    public function __construct(private readonly string $root,private readonly array $config,private readonly PythonClient $python,private readonly ?RedisClient $redis=null){}
    public function report():array
    {
        $checks=[];
        $checks['php']=['status'=>'healthy','detail'=>PHP_VERSION];
        $checks['filesystem']=['status'=>is_writable($this->root.'/storage')?'healthy':'error','detail'=>'storage'];
        $engine=false;try{$engine=$this->python->available();}catch(\Throwable){} $checks['python']=['status'=>$engine?'healthy':'error','detail'=>$this->config['engine']['url']??''];
        if(($this->config['runtime']??'file')==='redis'){$ok=$this->redis?->available()??false;$checks['redis']=['status'=>$ok?'healthy':'error','detail'=>$this->config['redis']['host'].':'.$this->config['redis']['port']];}
        else $checks['redis']=['status'=>'not_configured','detail'=>'file runtime active'];
        $checks['security']=['status'=>$this->securityReady()?'healthy':'warning','detail'=>($this->config['env']??'local')];
        $overall=in_array('error',array_column($checks,'status'),true)?'degraded':'healthy';
        return ['status'=>$overall,'checks'=>$checks,'time'=>gmdate('c')];
    }
    private function securityReady():bool
    {
        if(($this->config['env']??'local')!=='production')return true;
        return !($this->config['debug']??true)&&!($this->config['admin']['dev_bypass']??true)&&strlen((string)($this->config['security']['app_key']??''))>=24&&($this->config['security']['secure_cookie']??false);
    }
}
