<?php
namespace PuneMirror\Services;

use PuneMirror\Core\Request;
use PuneMirror\Core\Response;
use PuneMirror\Core\Session;

final class SecurityKernel
{
    public function __construct(private readonly RateLimiter $rateLimiter, private readonly array $config) {}

    public function enforce(Request $request): void
    {
        $maxBytes = (int)($this->config['security']['max_request_bytes'] ?? 1048576);
        $len = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($len > $maxBytes) Response::error('REQUEST_TOO_LARGE', 'Request body is too large', 413);

        $subject = $request->clientIp();
        if ($request->path === '/api/admin/session' && $request->method === 'POST') {
            $r = $this->rateLimiter->hit('admin-login', $subject, (int)($this->config['security']['login_rate_limit'] ?? 10), 300);
            if (!$r['allowed']) { header('Retry-After: ' . max(1, $r['reset_at'] - time())); Response::error('RATE_LIMITED','Too many sign-in attempts',429); }
        } elseif (in_array($request->method, ['POST','PATCH','PUT','DELETE'], true)) {
            $limit = str_starts_with($request->path, '/api/admin/') ? 120 : 60;
            $r = $this->rateLimiter->hit('mutation', $subject, $limit, 60);
            if (!$r['allowed']) { header('Retry-After: ' . max(1, $r['reset_at'] - time())); Response::error('RATE_LIMITED','Too many requests',429); }
        }

        if (($this->config['security']['csrf'] ?? true) && in_array($request->method, ['POST','PATCH','PUT','DELETE'], true) && str_starts_with($request->path, '/api/')) {
            Session::start();
            $token = $request->header('x-csrf-token') ?? (string)($request->body['_csrf'] ?? '');
            if (!Session::validateCsrf($token)) Response::error('CSRF_FAILED','Invalid or missing CSRF token',419);
            $origin = $request->header('origin');
            if ($origin && !$this->sameOrigin($origin)) Response::error('ORIGIN_REJECTED','Cross-origin state change rejected',403);
        }
    }

    private function sameOrigin(string $origin): bool
    {
        $expected=parse_url((string)($this->config['url']??''));
        $actual=parse_url($origin);
        if(!$expected||!$actual)return false;
        $schemeExpected=strtolower((string)($expected['scheme']??''));
        $schemeActual=strtolower((string)($actual['scheme']??''));
        $hostExpected=strtolower((string)($expected['host']??''));
        $hostActual=strtolower((string)($actual['host']??''));
        $portExpected=(int)($expected['port']??($schemeExpected==='https'?443:80));
        $portActual=(int)($actual['port']??($schemeActual==='https'?443:80));
        return $schemeExpected!==''&&$schemeExpected===$schemeActual&&$hostExpected!==''&&$hostExpected===$hostActual&&$portExpected===$portActual;
    }
}
