<?php
namespace PuneMirror\Services;

use PuneMirror\Core\JsonStore;
use RuntimeException;

final class AnalyticsService
{
    private const EVENTS=[
        'page_view','story_impression','story_open','story_read_25','story_read_50','story_read_90',
        'share','bookmark','follow','search','notification_open','reel_view','gallery_complete'
    ];

    public function __construct(private readonly JsonStore $store) {}

    public function track(string $userId,string $event,array $properties=[]):array
    {
        if(!in_array($event,self::EVENTS,true))throw new RuntimeException('Unsupported analytics event');
        $clean=[];
        foreach(array_slice($properties,0,20,true) as $key=>$value){
            $key=preg_replace('/[^a-z0-9_.-]/i','',(string)$key)??'';
            if($key==='')continue;
            if(is_bool($value)||is_int($value)||is_float($value)||$value===null)$clean[$key]=$value;
            elseif(is_string($value))$clean[$key]=mb_substr($value,0,240);
        }
        $row=['event'=>$event,'user_id'=>$userId,'properties'=>$clean,'occurred_at'=>gmdate('c')];
        $this->store->appendEvent('analytics',$row);
        return $row;
    }
}
