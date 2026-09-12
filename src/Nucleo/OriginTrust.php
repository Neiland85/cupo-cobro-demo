<?php

declare(strict_types=1);

namespace App\Nucleo;

/**
 * El producto decide provenance/trust desde el origen.
 * El agente no. El LLM no.
 */
final readonly class OriginTrust
{
    private function __construct(
        public string $provenance,
        public string $trust,
    ) {
    }

    public static function fromSource(string $source): self
    {
        $source = strtolower(trim($source));

        if ($source === 'operator') {
            return new self('operator', 'trusted');
        }

        $provenance = $source === '' ? 'unknown' : $source;

        return new self($provenance, 'untrusted');
    }

    public function mayInstruct(): bool
    {
        return $this->trust === 'trusted' && $this->provenance === 'operator';
    }
}
