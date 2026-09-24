<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;

final class PushSubscriptionService
{
    public function __construct(private readonly JsonStore $store){}

    public function save(string $userId,array $subscription):array
    {
        $endpoint=trim((string)($subscription['endpoint']??''));
        if($endpoint===''||!preg_match('#^https://#i',$endpoint))throw new \RuntimeException('Valid HTTPS push endpoint required');
        $hash=hash('sha256',$endpoint);
        foreach($this->store->all('push-subscriptions') as $row){
            if(($row['endpoint_hash']??'')!==$hash)continue;
            $row['user_id']=$userId;$row['subscription']=$subscription;$row['active']=true;
            return $this->store->put('push-subscriptions',$row);
        }
        return $this->store->put('push-subscriptions',[
            'id'=>UuidV7::generate(),'user_id'=>$userId,'endpoint_hash'=>$hash,
            'subscription'=>$subscription,'active'=>true,'transport_status'=>'registered'
        ]);
    }

    public function remove(string $userId,string $endpoint):bool
    {
        $hash=hash('sha256',trim($endpoint));
        foreach($this->store->all('push-subscriptions') as $row){
            if(($row['user_id']??'')!==$userId||($row['endpoint_hash']??'')!==$hash)continue;
            return $this->store->delete('push-subscriptions',(string)$row['id']);
        }
        return true;
    }

    public function forUser(string $userId):array
    {
        return array_values(array_filter($this->store->all('push-subscriptions'),fn($r)=>($r['user_id']??'')===$userId&&($r['active']??true)));
    }
}
