<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Context;

/** Verdad de fulfillment del tercero. Su timeout no confirma al cliente. */
final class Supplier
{
    public const INVARIANT = 'SupplierBookingConfirmed no es PaymentAuthorized.';
}
