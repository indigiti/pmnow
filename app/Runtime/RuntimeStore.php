<?php
namespace PuneMirror\Runtime;

interface RuntimeStore
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 300): void;
    public function incrementWindow(string $key, int $windowSeconds): array;
    public function publish(string $channel, array $payload): void;
}
