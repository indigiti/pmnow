<?php
namespace PuneMirror\Services;

use PuneMirror\Contracts\JobRepository;

final class WorkerService
{
    public function __construct(private readonly JobRepository $jobs,private readonly ContentHubService $hub,private readonly ?ErrorCenterService $errors=null){}
    public function runNext():?array
    {
        $job=$this->jobs->claimNext();if(!$job)return null;
        try{
            $result=match($job['type']??''){
                'SOURCE_SYNC'=>$this->hub->syncSource((string)($job['payload']['source_id']??$job['subject_id']),(int)($job['payload']['limit']??10),null),
                default=>throw new \RuntimeException('Unsupported job type')
            };
            $job['status']='completed';$job['completed_at']=gmdate('c');$job['result']=$result;$job['last_error']=null;
        }catch(\Throwable $e){
            $job['last_error']=$e->getMessage();$job['failed_at']=gmdate('c');
            $this->errors?->capture('JOB_FAILED',$e->getMessage(),['job_id'=>$job['id']??null,'job_type'=>$job['type']??null,'subject_id'=>$job['subject_id']??null]);
            if(($job['attempts']??0)<($job['max_attempts']??5)){
                $d=[60,300,900,1800,3600];$delay=$d[min(count($d)-1,max(0,(int)$job['attempts']-1))];
                $job['status']='queued';$job['available_at']=gmdate('c',time()+$delay);
            }else $job['status']='failed';
        }
        return $this->jobs->save($job);
    }
}
