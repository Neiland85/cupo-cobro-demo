<?php

declare(strict_types=1);

namespace Clarity\Nucleo\HoldPipe;

/**
 * El dominio habla este puerto. No habla JSON, HTTP ni el SDK del PSP.
 */
interface PaymentPort
{
    /** @return 'ok'|'reject'|'timeout' */
    public function authorize(string $idempotencyKey, string $bookingId): string;
}
