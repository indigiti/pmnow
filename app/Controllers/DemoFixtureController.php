<?php
declare(strict_types=1);

namespace PuneMirror\Controllers;

use PuneMirror\Core\JsonStore;
use PuneMirror\Core\Request;
use PuneMirror\Core\Response;
use PuneMirror\Services\AdminAuthService;
use PuneMirror\Services\DemoFixtureService;

final class DemoFixtureController
{
    public function __construct(
        private readonly AdminAuthService $auth,
        private readonly DemoFixtureService $fixtures,
        private readonly JsonStore $store,
    ) {}

    public function status(): never
    {
        $this->guard();
        header('Cache-Control: private, no-store');
        Response::json($this->fixtures->status());
    }

    public function mutate(Request $request, string $operation): never
    {
        $actor = $this->guard();
        $confirm = match ($operation) {
            'apply' => 'LOAD DEMO',
            'purge' => 'REMOVE DEMO',
            default => null,
        };
        if ($confirm === null) Response::error('NOT_FOUND', 'Unknown demo action', 404);
        if (!hash_equals($confirm, (string)($request->body['confirmation'] ?? ''))) {
            Response::error('DEMO_CONFIRMATION_REQUIRED', 'Enter the exact confirmation text to continue.', 422);
        }
        try {
            $result = $this->fixtures->execute($operation, true);
            $this->store->appendEvent('demo-management', [
                'operation'=>$operation,
                'actor_id'=>(string)$actor['id'],
                'fixture_suite'=>DemoFixtureService::SUITE,
                'inserted'=>$result['inserted'] ?? null,
                'removed'=>$result['removed'] ?? null,
            ]);
            header('Cache-Control: private, no-store');
            Response::json($result);
        } catch (\Throwable $e) {
            Response::error('DEMO_OPERATION_FAILED', $e->getMessage(), 409);
        }
    }

    private function guard(): array
    {
        // Fail closed on actual production, regardless of hidden UI or requested URL.
        if (!$this->fixtures->webEnabled()) {
            Response::error('NOT_FOUND', 'Demo management is not enabled', 404);
        }
        $user = $this->auth->user();
        if (!$user) Response::error('ADMIN_AUTH_REQUIRED', 'Admin authentication required', 401);
        if (($user['role'] ?? '') !== 'super_admin') {
            Response::error('FORBIDDEN', 'Super-admin access required', 403);
        }
        return $user;
    }
}
