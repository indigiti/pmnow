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

if (!function_exists('pm_icon')) {
    function pm_icon(string $name, string $class = ''): string {
        $icons = [
            'home' => '<path d="M3.5 10.8 12 3.8l8.5 7v8.4a1.8 1.8 0 0 1-1.8 1.8h-4.4v-6h-4.6v6H5.3a1.8 1.8 0 0 1-1.8-1.8z"/>',
            'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.2 4.2"/>',
            'play' => '<path d="m9 7 9 5-9 5z"/>',
            'user' => '<circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c.8-4 3-6 6.5-6s5.7 2 6.5 6"/>',
            'bell' => '<path d="M18 9a6 6 0 0 0-12 0c0 6-2.5 6-2.5 7.5h17C20.5 15 18 15 18 9Z"/><path d="M10 20h4"/>',
            'pin' => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.2"/>',
            'bookmark' => '<path d="M6 4.5A1.5 1.5 0 0 1 7.5 3h9A1.5 1.5 0 0 1 18 4.5V21l-6-4-6 4z"/>',
            'heart' => '<path d="M20.8 8.6c0 5.1-8.8 10.2-8.8 10.2S3.2 13.7 3.2 8.6A4.6 4.6 0 0 1 12 6.7a4.6 4.6 0 0 1 8.8 1.9Z"/>',
            'comment' => '<path d="M20 15.5A3.5 3.5 0 0 1 16.5 19H8l-4 2v-5.5A3.5 3.5 0 0 1 3 13V7.5A3.5 3.5 0 0 1 6.5 4h10A3.5 3.5 0 0 1 20 7.5Z"/>',
            'share' => '<path d="M12 3v12"/><path d="m7.5 7.5 4.5-4.5 4.5 4.5"/><path d="M5 12.5V19a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6.5"/>',
            'arrow-left' => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
            'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
            'follow' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="2.5"/>',
            'grid' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
            'external' => '<path d="M14 4h6v6"/><path d="m20 4-9 9"/><path d="M18 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6"/>',
        ];
        $body = $icons[$name] ?? $icons['grid'];
        $safeClass = trim(preg_replace('/[^a-zA-Z0-9 _-]/', '', $class) ?? '');
        $classAttr = trim('pm-icon ' . $safeClass);
        return '<svg class="' . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}

return ['root' => $root, 'config' => $config];
