<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Evidence;

final class Hash
{
    public static function of(string $payload): string
    {
        return 'sha256:'.substr(hash('sha256', $payload), 0, 8);
    }

    public static function link(string $prev, string $payload): string
    {
        return self::of($prev.'|'.$payload);
    }
}
