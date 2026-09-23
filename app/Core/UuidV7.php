<?php
namespace PuneMirror\Core;

final class UuidV7
{
    public static function generate(): string
    {
        $ms = (int) floor(microtime(true) * 1000);
        $time = str_pad(dechex($ms), 12, '0', STR_PAD_LEFT);
        $hex = $time . bin2hex(random_bytes(10));
        $hex[12] = '7';
        $variant = hexdec($hex[16]);
        $hex[16] = dechex(($variant & 0x3) | 0x8);
        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4),
            substr($hex, 16, 4), substr($hex, 20, 12)
        );
    }

    public static function isValid(string $id): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id);
    }
}
