<?php

declare(strict_types=1);

namespace Clarity\Nucleo\HoldPipe;

final class PaymentStub implements PaymentPort
{
    public function __construct(private readonly string $mode)
    {
    }

    public function authorize(string $idempotencyKey, string $bookingId): string
    {
        unset($idempotencyKey, $bookingId);

        return $this->mode;
    }
}
