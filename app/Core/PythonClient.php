<?php
namespace PuneMirror\Core;

use RuntimeException;

final class PythonClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly float $timeout = 20.0
    ) {}

    public function available(): bool
    {
        try {
            $this->get('/health');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path, null);
    }

    public function post(string $path, array $payload): array
    {
        return $this->request('POST', $path, $payload);
    }

    private function request(string $method, string $path, ?array $payload): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        $headers = "Accept: application/json\r\n";
        $content = '';
        if ($payload !== null) {
            $content = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $headers .= "Content-Type: application/json\r\nContent-Length: " . strlen($content) . "\r\n";
        }
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $headers,
                'content' => $content,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $m)) { $status = (int)$m[1]; break; }
        }
        if ($raw === false) throw new RuntimeException("Python service unavailable at {$url}");
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) throw new RuntimeException("Invalid Python response from {$url}");
        if ($status >= 400) {
            $detail = $decoded['detail'] ?? $decoded['error'] ?? ('HTTP ' . $status);
            if (is_array($detail)) $detail = json_encode($detail);
            throw new RuntimeException((string)$detail);
        }
        return $decoded;
    }
}
