<?php
declare(strict_types=1);

/**
 * A short-lived, tightly scoped staging-only setup convenience.
 *
 * This ONLY removes the need to set PMNOW_DEMO_DATA_ALLOWED by hand when the
 * trusted stage.digiti.in virtual host serves /pmnow. It does not bypass
 * authentication, authorization, CSRF, editorial controls or production config.
 *
 * Remove this exception after 2026-11-30 (Asia/Kolkata).
 */
if (!function_exists('pm_temporary_stage_demo_overrides')) {
    function pm_temporary_stage_demo_overrides(array $server, array $existing, ?int $now = null): array
    {
        $expiresAt = strtotime('2026-11-30T23:59:59+05:30');
        if (($now ?? time()) > $expiresAt) return [];

        // HTTP_HOST alone can be supplied by an attacker, so require the
        // canonical server-selected virtual host to agree independently.
        $host = strtolower(trim((string)($server['HTTP_HOST'] ?? '')));
        $serverName = strtolower(trim((string)($server['SERVER_NAME'] ?? '')));
        if ($host !== 'stage.digiti.in' || $serverName !== 'stage.digiti.in') return [];

        $uriPath = parse_url((string)($server['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if (!is_string($uriPath) || ($uriPath !== '/pmnow' && !str_starts_with($uriPath, '/pmnow/'))) return [];

        $https = strtolower((string)($server['HTTPS'] ?? '')) === 'on'
            || (string)($server['SERVER_PORT'] ?? '') === '443'
            || strtolower((string)($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        if (!$https) return [];

        $env = strtolower(trim((string)($existing['APP_ENV'] ?? '')));
        if ($env !== '' && $env !== 'staging') return [];

        // Respect an explicit opt-out; do not override actual production config.
        $flag = strtolower(trim((string)($existing['PMNOW_DEMO_DATA_ALLOWED'] ?? '')));
        if ($flag !== '' && !filter_var($flag, FILTER_VALIDATE_BOOL)) return [];

        $configuredUrl = trim((string)($existing['APP_URL'] ?? ''));
        if ($configuredUrl !== '') {
            $parts = parse_url($configuredUrl);
            if (!is_array($parts)
                || strtolower((string)($parts['host'] ?? '')) !== 'stage.digiti.in'
                || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
                || rtrim((string)($parts['path'] ?? ''), '/') !== '/pmnow') {
                return [];
            }
        }
        $configuredBase = trim((string)($existing['APP_BASE_PATH'] ?? ''));
        if ($configuredBase !== '' && rtrim($configuredBase, '/') !== '/pmnow') return [];

        // These are request-local values; no file, credential, or site-wide
        // configuration is modified. Stronger security defaults remain intact.
        return [
            'APP_ENV'=>'staging',
            'APP_URL'=>'https://stage.digiti.in/pmnow',
            'APP_BASE_PATH'=>'/pmnow',
            'PMNOW_DEMO_DATA_ALLOWED'=>'true',
            'ADMIN_DEV_BYPASS'=>'false',
            'APP_DEBUG'=>'false',
            'SESSION_SECURE_COOKIE'=>'true',
            'HSTS_ENABLED'=>'true',
        ];
    }
}
