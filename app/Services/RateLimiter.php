<?php
namespace PuneMirror\Services;

use PuneMirror\Runtime\RuntimeStore;

final class RateLimiter
{
    public function __construct(private readonly RuntimeStore $runtime) {}

    public function hit(string $bucket, string $subject, int $limit, int $windowSeconds): array
    {
        $key = 'rate:' . hash('sha256', $bucket . '|' . $subject);
        $now = time();
        $row = $this->runtime->get($key);
        if (!is_array($row) || ($row['reset_at'] ?? 0) <= $now) $row = ['count'=>0,'reset_at'=>$now+$windowSeconds];
        $row['count'] = (int)($row['count'] ?? 0) + 1;
        $ttl = max(1, (int)$row['reset_at'] - $now);
        $this->runtime->set($key, $row, $ttl);
        return ['allowed'=>$row['count'] <= $limit,'limit'=>$limit,'remaining'=>max(0,$limit-$row['count']),'reset_at'=>$row['reset_at']];
    }
}
