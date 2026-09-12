<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Capability;

final readonly class Decision
{
    private function __construct(
        public bool $allowed,
        public string $policy,
        public string $authorization,
    ) {
    }

    public static function allow(string $policy): self
    {
        return new self(true, $policy, 'ALLOW');
    }

    public static function deny(string $policy): self
    {
        return new self(false, $policy, 'DENIED');
    }
}
