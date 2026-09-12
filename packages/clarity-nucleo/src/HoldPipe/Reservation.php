<?php

declare(strict_types=1);

namespace Clarity\Nucleo\HoldPipe;

final class Reservation
{
    public function __construct(
        public readonly string $id,
        public readonly string $date,
        public string $status,
        public readonly string $idempotencyKey,
        public bool $replay = false,
    ) {
    }
}
