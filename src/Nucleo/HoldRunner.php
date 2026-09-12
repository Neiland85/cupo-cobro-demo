<?php

declare(strict_types=1);

namespace App\Nucleo;

use Clarity\Nucleo\HoldPipe\PaymentPort;
use Clarity\Nucleo\HoldPipe\Pipe;

/**
 * Único caller de producto. No acepta trust suelto.
 */
final class HoldRunner
{
    public function __construct(
        private readonly Pipe $pipe,
        private readonly PaymentPort $payment,
    ) {
    }

    /**
     * @return array{
     *   reservation: mixed,
     *   steps: list<array{id:string,label:string,verdict:string,detail:string}>,
     *   evidence: mixed,
     *   blocked: bool,
     *   message: string
     * }
     */
    public function run(
        string $agent,
        string $capability,
        string $key,
        string $date,
        OriginTrust $origin,
    ): array {
        return $this->pipe->run(
            'product',
            $agent,
            $capability,
            $key,
            $date,
            $this->payment,
            null,
            $origin->provenance,
            $origin->trust,
        );
    }
}
