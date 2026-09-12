<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Context;

/** Movimiento de dinero. Timeout no autoriza. */
final class Payment
{
    public const INVARIANT = 'El movimiento es atribuible, idempotente y reconciliable.';
}
