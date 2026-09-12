<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class BreakRecord
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $plane,
        public readonly string $key,
        public readonly string $owner,
        public string $state = 'open',
        public ?string $reason = null,
        public array $evidence = [],
    ) {
    }
}
