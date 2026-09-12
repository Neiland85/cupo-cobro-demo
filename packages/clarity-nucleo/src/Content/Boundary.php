<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Content;

use Clarity\Nucleo\Capability\Decision;

/**
 * Todo contenido es datos hasta que una frontera de confianza demuestre lo contrario.
 * El LLM no es esta frontera.
 */
final class Boundary
{
    public function admit(string $provenance, string $trust): Decision
    {
        if ($trust !== 'trusted') {
            return Decision::deny('untrusted-content');
        }

        if ($provenance !== 'operator') {
            return Decision::deny('untrusted-content');
        }

        return Decision::allow('content-boundary');
    }
}
