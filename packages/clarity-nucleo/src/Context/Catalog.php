<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Context;

/** Precio publicado. Puede cambiar sin que exista una reserva. */
final class Catalog
{
    public const INVARIANT = 'Una oferta publicada tiene identidad y un precio que cambia sin reserva.';
}
