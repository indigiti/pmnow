<?php
declare(strict_types=1);

/**
 * Portable environment access for hosts that disable putenv() (for example Cloudways PHP-FPM).
 * Values set at runtime are mirrored into $_ENV and $_SERVER; putenv() is only an optional bridge.
 */
if (!function_exists('pm_env')) {
    function pm_env(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) return $_ENV[$key];
        if (array_key_exists($key, $_SERVER)) return $_SERVER[$key];

        if (function_exists('getenv')) {
            $value = getenv($key);
            if ($value !== false) return $value;
        }

        return $default;
    }
}

if (!function_exists('pm_env_set')) {
    function pm_env_set(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;

        // Disabled functions are undefined on modern PHP, so guard this bridge.
        if (function_exists('putenv')) {
            @putenv($key . '=' . $value);
        }
    }
}
