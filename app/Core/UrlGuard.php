<?php
namespace PuneMirror\Core;

use RuntimeException;

final class UrlGuard
{
    public static function assertPublicHttpUrl(string $url, bool $allowLocal = false): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (!$parts || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http','https'], true)) {
            throw new RuntimeException('Only http/https URLs are allowed');
        }
        if (!empty($parts['user']) || !empty($parts['pass'])) throw new RuntimeException('URLs with embedded credentials are not allowed');
        $host = strtolower(rtrim((string)($parts['host'] ?? ''), '.'));
        if ($host === '') throw new RuntimeException('URL host is required');
        if (!$allowLocal && in_array($host, ['localhost','localhost.localdomain'], true)) throw new RuntimeException('Localhost URLs are blocked');
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            self::assertPublicIp($host, $allowLocal);
        } elseif (!$allowLocal) {
            $ips = gethostbynamel($host) ?: [];
            foreach ($ips as $ip) self::assertPublicIp($ip, false);
        }
        return $url;
    }

    public static function isSafeMediaUrl(string $url): bool
    {
        if (str_starts_with($url, '/media/')) return true;
        try { self::assertPublicHttpUrl($url, false); return true; }
        catch (\Throwable) { return false; }
    }

    private static function assertPublicIp(string $ip, bool $allowLocal): void
    {
        if ($allowLocal) return;
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) throw new RuntimeException('Private, loopback, reserved, and link-local addresses are blocked');
        if (str_starts_with($ip, '169.254.')) throw new RuntimeException('Link-local addresses are blocked');
    }
}
