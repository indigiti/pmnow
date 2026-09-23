<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once __DIR__ . '/env.php';

$envFile = $root . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (pm_env($key, null) === null) pm_env_set($key, trim($value, "\"'"));
    }
}

spl_autoload_register(function (string $class) use ($root): void {
    $prefix = 'PuneMirror\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $path = $root . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require $path;
});

$config = require $root . '/config/app.php';
date_default_timezone_set($config['timezone']);
\PuneMirror\Core\Session::configure($config['security'] ?? []);

if (!function_exists('pm_e')) {
    function pm_e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('pm_base_path')) {
    function pm_base_path(): string {
        static $base=null;
        if($base!==null)return $base;
        $raw=pm_env('APP_BASE_PATH',null);
        if($raw===null || trim((string)$raw)===''){
            $url=(string)pm_env('APP_URL','');
            $raw=(string)(parse_url($url,PHP_URL_PATH)?:'');
        }
        $base='/' . trim((string)$raw,'/');
        if($base==='/')$base='';
        return $base;
    }
}
if (!function_exists('pm_url')) {
    function pm_url(string $path=''): string {
        if(preg_match('#^https?://#i',$path))return $path;
        $path='/' . ltrim($path,'/');
        if($path==='/')$path='';
        return pm_base_path().$path ?: '/';
    }
}
if (!function_exists('pm_media')) {
    function pm_media(?array $media): string {
        $url=$media ? (string)($media['url'] ?? '') : '/media/home_rain.jpg';
        return preg_match('#^https?://#i',$url)?$url:pm_url($url);
    }
}

if (!function_exists('pm_asset')) {
    function pm_asset(string $source, string $fallback): string {
        $root = dirname(__DIR__);
        $manifestPath = $root . '/public/build/.vite/manifest.json';
        if (is_file($manifestPath)) {
            try {
                $manifest = json_decode((string)file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
                if (isset($manifest[$source]['file'])) return pm_url('/build/' . ltrim((string)$manifest[$source]['file'], '/'));
            } catch (\Throwable) {}
        }
        return pm_url($fallback);
    }
}

if (!function_exists('pm_story_path')) {
    function pm_story_path(array $story): string {
        return match ($story['type'] ?? 'article') {
            'gallery' => pm_url('/gallery/' . $story['id']),
            'developing' => pm_url('/developing/' . $story['id']),
            'live' => pm_url('/live/' . $story['id']),
            default => pm_url('/story/' . $story['id']),
        };
    }
}

return ['root' => $root, 'config' => $config];
