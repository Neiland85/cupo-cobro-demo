<?php

declare(strict_types=1);

namespace App\Nucleo;

use Clarity\Nucleo\HoldPipe\PaymentPort;

/**
 * Adapter del producto. Default timeout: no hay PSP todavía.
 * No nombra Stripe. El dominio habla este puerto.
 */
final class PaymentAdapter implements PaymentPort
{
    public function __construct(private readonly string $mode = 'timeout')
    {
    }

    public function authorize(string $idempotencyKey, string $bookingId): string
    {
        unset($idempotencyKey, $bookingId);

        return $this->mode;
    }
}
