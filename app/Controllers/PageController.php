<?php
namespace PuneMirror\Controllers;

use PuneMirror\Core\Response;
use PuneMirror\Core\View;
use PuneMirror\Services\StoryService;
use PuneMirror\Services\UserStateService;
use PuneMirror\Services\NotificationService;

final class PageController
{
    public function __construct(private readonly View $view, private readonly StoryService $stories, private readonly UserStateService $users, private readonly NotificationService $notifications) {}

    public function home(): never
    {
        $this->page('pages/home', ['stories' => $this->stories->feed(12), 'userState' => $this->users->state(), 'activeNav' => 'home']);
    }

    public function explore(): never
    {
        $this->page('pages/explore', ['stories' => $this->stories->feed(12), 'activeNav' => 'explore', 'userState' => $this->users->state()]);
    }

    public function story(string $id): never
    {
        $story = $this->stories->find($id);
        if (!$story) Response::html('<h1>Story not found</h1>', 404);
        $this->page('pages/story', ['story' => $story, 'activeNav' => '', 'userState' => $this->users->state()]);
    }

    public function gallery(string $id): never
    {
        $story = $this->stories->find($id);
        if (!$story) Response::html('<h1>Gallery not found</h1>', 404);
        $this->page('pages/gallery', ['story' => $story, 'activeNav' => '', 'userState' => $this->users->state()]);
    }

    public function developing(string $id): never
    {
        $story = $this->stories->find($id);
        if (!$story) Response::html('<h1>Developing story not found</h1>', 404);
        $this->page('pages/developing', ['story' => $story, 'activeNav' => '', 'userState' => $this->users->state()]);
    }

    public function live(string $id): never
    {
        $story = $this->stories->find($id);
        if (!$story) Response::html('<h1>Live story not found</h1>', 404);
        $live = $this->stories->liveStoryByStory($id);
        $this->page('pages/live', ['story' => $story, 'live' => $live, 'activeNav' => '', 'userState' => $this->users->state()]);
    }

    public function watch(): never
    {
        $reels = $this->stories->byType('reel', 10);
        if (!$reels) $reels = array_slice($this->stories->feed(10), 0, 5);
        $this->page('pages/watch', ['reels' => $reels, 'activeNav' => 'watch', 'userState' => $this->users->state()]);
    }


    public function notifications(): never
    {
        $state = $this->users->state();
        $rows = $this->notifications->forUser((string)$state['user']['id']);
        $this->page('pages/notifications', ['notifications'=>$rows,'activeNav'=>'notifications','userState'=>$state]);
    }

    public function profile(): never
    {
        $state = $this->users->state();
        $all = $this->stories->feed(50);
        $saved = array_values(array_filter($all, fn($s) => in_array($s['id'], $state['bookmark_story_ids'], true)));
        $this->page('pages/profile', ['state' => $state, 'saved' => $saved, 'activeNav' => 'profile', 'userState' => $state]);
    }

    private function page(string $contentView, array $data): never
    {
        $state = $data['userState'] ?? $this->users->state();
        $unread = 0; foreach ($this->notifications->forUser((string)$state['user']['id']) as $n) if (empty($n['read_at'])) $unread++;
        $data['userState'] = $state; $data['unreadNotifications'] = $unread;
        $content = $this->view->render($contentView, $data);
        $html = $this->view->render('layout', $data + ['content' => $content]);
        Response::html($html);
    }
}
