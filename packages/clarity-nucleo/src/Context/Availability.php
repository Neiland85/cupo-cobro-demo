<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Context;

/** Hold de capacidad. Caducar no es cancelar. */
final class Availability
{
    public const INVARIANT = 'No se retiene más capacidad de la que existe. Un hold caduca.';
}
