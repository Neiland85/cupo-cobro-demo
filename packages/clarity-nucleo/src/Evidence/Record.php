<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Evidence;

final readonly class Record
{
    public function __construct(
        public string $id,
        public int $at,
        public string $agent,
        public string $task,
        public string $capability,
        public string $policy,
        public string $authorization,
        public string $result,
        public string $hash,
    ) {
    }
}
