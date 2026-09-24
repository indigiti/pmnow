<?php
namespace PuneMirror\Core;

use RuntimeException;

final class IndexManager
{
    public function __construct(private readonly string $indexRoot) {}

    public function write(string $name, array $payload): void
    {
        if (!preg_match('/^[a-z0-9_-]+$/i', $name)) throw new RuntimeException('Invalid index name');
        $path = $this->indexRoot . '/' . $name . '.json';
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException("Cannot create index directory $dir");
        $lock = fopen($path . '.lock', 'c+');
        if (!$lock) throw new RuntimeException("Cannot open index lock for $name");
        $tmp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        try {
            if (!flock($lock, LOCK_EX)) throw new RuntimeException("Cannot lock index $name");
            $payload['_generated_at'] = gmdate('c');
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
            $fh = fopen($tmp, 'wb');
            if (!$fh) throw new RuntimeException("Cannot write temporary index $tmp");
            try {
                if (fwrite($fh, $json) === false) throw new RuntimeException("Cannot write index $name");
                fflush($fh);
                if (function_exists('fsync')) @fsync($fh);
            } finally {
                fclose($fh);
            }
            if (!rename($tmp, $path)) throw new RuntimeException("Cannot atomically replace index $name");
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
            if (is_file($tmp)) @unlink($tmp);
        }
    }

    public function read(string $name): ?array
    {
        if (!preg_match('/^[a-z0-9_-]+$/i', $name)) throw new RuntimeException('Invalid index name');
        $path = $this->indexRoot . '/' . $name . '.json';
        if (!is_file($path)) return null;
        $data = json_decode((string)file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }
}
