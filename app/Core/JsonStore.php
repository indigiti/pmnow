<?php
namespace PuneMirror\Core;

use RuntimeException;

final class JsonStore
{
    public function __construct(private readonly string $root) {}
    public function rootPath(): string { return $this->root; }

    public function get(string $collection, string $id): ?array
    {
        $path = $this->path($collection, $id);
        if (!is_file($path)) return null;
        $raw = file_get_contents($path);
        if ($raw === false) throw new RuntimeException("Cannot read $path");
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        return is_array($data) ? $data : null;
    }

    public function put(string $collection, array $record): array
    {
        $record['id'] ??= UuidV7::generate();
        if (!UuidV7::isValid((string)$record['id'])) throw new RuntimeException('Invalid UUIDv7');
        $record['_schema'] ??= rtrim($collection, 's');
        $record['_version'] ??= 1;
        $record['updated_at'] = gmdate('c');
        $record['created_at'] ??= $record['updated_at'];

        $dir = $this->root . '/' . $collection;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create $dir");
        }
        $path = $this->path($collection, (string)$record['id']);
        $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $lockPath = $dir . '/.write.lock';
        $lock = fopen($lockPath, 'c+');
        if (!$lock) throw new RuntimeException("Cannot open lock $lockPath");
        try {
            if (!flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock store');
            $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
            $fh = fopen($tmp, 'wb');
            if (!$fh) throw new RuntimeException("Cannot write $tmp");
            fwrite($fh, $json);
            fflush($fh);
            if (function_exists('fsync')) @fsync($fh);
            fclose($fh);
            if (!rename($tmp, $path)) throw new RuntimeException("Cannot atomically replace $path");
            return $record;
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
            if (is_file($tmp)) @unlink($tmp);
        }
    }

    public function delete(string $collection, string $id): bool
    {
        $path = $this->path($collection, $id);
        return !is_file($path) || unlink($path);
    }

    public function all(string $collection): array
    {
        $dir = $this->root . '/' . $collection;
        if (!is_dir($dir)) return [];
        $rows = [];
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $data = json_decode((string)file_get_contents($file), true);
            if (is_array($data)) $rows[] = $data;
        }
        return $rows;
    }

    public function appendEvent(string $stream, array $event): void
    {
        $dir = dirname($this->root) . '/events';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $event['id'] ??= UuidV7::generate();
        $event['at'] ??= gmdate('c');
        $file = $dir . '/' . preg_replace('/[^a-z0-9_-]/i', '-', $stream) . '-' . date('Y-m-d') . '.jsonl';
        file_put_contents($file, json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }

    private function path(string $collection, string $id): string
    {
        if (!preg_match('/^[a-z0-9_-]+$/i', $collection)) throw new RuntimeException('Invalid collection');
        if (!preg_match('/^[0-9a-f-]+$/i', $id)) throw new RuntimeException('Invalid record id');
        return $this->root . '/' . $collection . '/' . strtolower($id) . '.json';
    }
}
