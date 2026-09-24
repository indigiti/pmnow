<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\UuidV7;

final class NotificationService
{
    public function __construct(private readonly JsonStore $store) {}

    public function forUser(string $userId, int $limit = 50): array
    {
        $rows = array_values(array_filter($this->store->all('notifications'), fn($n) => ($n['user_id'] ?? '') === $userId));
        usort($rows, fn($a,$b) => strcmp((string)($b['created_at'] ?? ''),(string)($a['created_at'] ?? '')));
        return array_slice($rows, 0, $limit);
    }

    public function notifyUser(string $userId,string $type,string $title,string $body='',?string $storyId=null,?string $distributionKey=null,array $meta=[]):?array
    {
        if($distributionKey){
            foreach($this->store->all('notifications') as $row){
                if(($row['user_id']??'')===$userId&&($row['distribution_key']??'')===$distributionKey)return null;
            }
        }
        return $this->store->put('notifications',[
            'id'=>UuidV7::generate(),'user_id'=>$userId,'story_id'=>$storyId,
            'type'=>$type,'title'=>$title,'body'=>$body,'read_at'=>null,
            'distribution_key'=>$distributionKey,'meta'=>$meta,
        ]);
    }

    public function notifyFollowers(string $storyId, string $type, string $title, string $body = ''): int
    {
        $count = 0;
        foreach ($this->store->all('follows') as $follow) {
            if (($follow['story_id'] ?? '') !== $storyId) continue;
            if($this->notifyUser((string)$follow['user_id'],$type,$title,$body,$storyId,'follow:'.$type.':'.$storyId))$count++;
        }
        return $count;
    }

    public function markRead(string $userId, string $notificationId): ?array
    {
        $row = $this->store->get('notifications', $notificationId);
        if (!$row || ($row['user_id'] ?? '') !== $userId) return null;
        $row['read_at'] = gmdate('c');
        return $this->store->put('notifications', $row);
    }
}
