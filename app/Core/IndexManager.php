<?php
namespace PuneMirror\Core;

final class IndexManager
{
    public function __construct(private readonly string $indexRoot) {}

    public function write(string $name, array $payload): void
    {
        $path = $this->indexRoot . '/' . $name . '.json';
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $tmp = $path . '.tmp';
        $payload['_generated_at'] = gmdate('c');
        file_put_contents($tmp, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX);
        rename($tmp, $path);
    }

    public function read(string $name): ?array
    {
        $path = $this->indexRoot . '/' . $name . '.json';
        if (!is_file($path)) return null;
        $data = json_decode((string)file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }
}
