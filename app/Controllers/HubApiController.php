<?php
namespace PuneMirror\Controllers;

use PuneMirror\Core\Request;
use PuneMirror\Core\Response;
use PuneMirror\Core\PythonClient;
use PuneMirror\Services\AdminAuthService;
use PuneMirror\Services\ContentHubService;
use PuneMirror\Services\SourceService;

final class HubApiController
{
    public function __construct(
        private readonly SourceService $sources,
        private readonly ContentHubService $hub,
        private readonly PythonClient $python,
        private readonly AdminAuthService $auth
    ) {}

    public function engineHealth(): never
    {
        $this->guard('system.view');
        try { Response::json($this->python->get('/health')); }
        catch (\Throwable $e) { Response::error('ENGINE_UNAVAILABLE', $e->getMessage(), 503); }
    }

    public function providers(): never
    {
        $this->guard('sources.view');
        try { Response::json($this->python->get('/v1/providers')['data'] ?? []); }
        catch (\Throwable $e) { Response::error('ENGINE_UNAVAILABLE', $e->getMessage(), 503); }
    }

    public function sources(): never { $this->guard('sources.view'); Response::json($this->sources->all()); }

    public function createSource(Request $request): never
    {
        $this->guard('sources.manage');
        try { Response::json($this->sources->create($request->body), 201); }
        catch (\Throwable $e) { Response::error('SOURCE_INVALID', $e->getMessage(), 422); }
    }

    public function updateSource(Request $request, string $id): never
    {
        $this->guard('sources.manage');
        try { Response::json($this->sources->update($id,$request->body)); }
        catch (\Throwable $e) { Response::error('SOURCE_UPDATE_FAILED',$e->getMessage(),422); }
    }

    public function setCredentials(Request $request, string $id): never
    {
        $this->guard('sources.manage');
        try { Response::json(['masked'=>$this->sources->setCredentials($id,(array)($request->body['credentials']??[]))]); }
        catch (\Throwable $e) { Response::error('CREDENTIAL_UPDATE_FAILED',$e->getMessage(),422); }
    }

    public function deleteSource(string $id): never
    {
        $this->guard('sources.manage');
        try { Response::json(['deleted'=>$this->sources->delete($id)]); }
        catch (\Throwable $e) { Response::error('SOURCE_DELETE_FAILED',$e->getMessage(),422); }
    }

    public function testSource(string $id): never
    {
        $this->guard('sources.manage');
        try { Response::json($this->sources->test($id)); }
        catch (\Throwable $e) { Response::error('SOURCE_TEST_FAILED', $e->getMessage(), 422); }
    }

    public function syncSource(Request $request, string $id): never
    {
        $this->guard('sources.manage');
        try {
            $publish = array_key_exists('publish', $request->body) ? (bool)$request->body['publish'] : null;
            Response::json($this->hub->syncSource($id, (int)($request->body['limit'] ?? 10), $publish));
        } catch (\Throwable $e) { Response::error('SOURCE_SYNC_FAILED', $e->getMessage(), 422); }
    }

    public function content(): never { $this->guard('content.review'); Response::json($this->hub->allContent()); }
    public function clusters(): never { $this->guard('content.review'); Response::json($this->hub->clusters()); }

    public function importManual(Request $request): never
    {
        $this->guard('content.edit');
        try {
            $sourceId = isset($request->body['source_id']) ? (string)$request->body['source_id'] : null;
            $publish = (bool)($request->body['publish'] ?? true);
            $raw = $request->body['content'] ?? $request->body;
            if (!is_array($raw)) $raw = [];
            unset($raw['source_id'], $raw['publish']);
            Response::json($this->hub->manualImport($raw, $sourceId, $publish), 201);
        } catch (\Throwable $e) { Response::error('IMPORT_FAILED', $e->getMessage(), 422); }
    }

    public function analyze(string $id): never
    {
        $this->guard('content.review');
        try { Response::json($this->hub->analyzeExisting($id)); }
        catch (\Throwable $e) { Response::error('ANALYSIS_FAILED', $e->getMessage(), 422); }
    }

    private function guard(string $permission): array
    {
        try { return $this->auth->require($permission); }
        catch (\Throwable) { Response::error('ADMIN_AUTH_REQUIRED','Admin authentication or permission required',401); }
    }
}
