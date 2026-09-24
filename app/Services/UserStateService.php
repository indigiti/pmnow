<?php
namespace PuneMirror\Services;

use PuneMirror\Contracts\UserStateRepository;
use PuneMirror\Core\Session;
use PuneMirror\Core\UuidV7;

final class UserStateService
{
    public function __construct(private readonly UserStateRepository $repo) {}

    public function currentUser(): array
    {
        Session::start();
        $id = Session::get('user_id');
        if ($id && ($user = $this->repo->user($id))) return $user;
        $user = $this->repo->saveUser([
            'id' => UuidV7::generate(),
            'name' => 'Pune Reader',
            'email' => null,
            'role' => 'reader',
            'preferences' => ['city' => 'Pune', 'areas' => [], 'channels' => []],
        ]);
        Session::put('user_id', $user['id']);
        return $user;
    }

    public function state(): array
    {
        $user = $this->currentUser();
        return [
            'user' => $user,
            'bookmark_story_ids' => array_values(array_column($this->repo->bookmarks($user['id']), 'story_id')),
            'follow_story_ids' => array_values(array_column($this->repo->follows($user['id']), 'story_id')),
        ];
    }

    public function toggleBookmark(string $storyId): bool
    {
        return $this->repo->toggleBookmark($this->currentUser()['id'], $storyId);
    }

    public function toggleFollow(string $storyId): bool
    {
        return $this->repo->toggleFollow($this->currentUser()['id'], $storyId);
    }

    public function updatePreferences(array $preferences): array
    {
        $user=$this->currentUser();
        $current=(array)($user['preferences']??[]);
        $areas=$this->cleanList((array)($preferences['areas']??$current['areas']??[]));
        $channels=$this->cleanList((array)($preferences['channels']??$current['channels']??[]));
        $user['preferences']=[
            'city'=>(string)($current['city']??'Pune'),
            'areas'=>$areas,
            'channels'=>$channels,
        ];
        return $this->repo->saveUser($user);
    }

    private function cleanList(array $values):array
    {
        $out=[];
        foreach($values as $value){
            $value=trim((string)$value);
            if($value===''||mb_strlen($value)>60)continue;
            $out[strtolower($value)]=$value;
            if(count($out)>=12)break;
        }
        return array_values($out);
    }
}
