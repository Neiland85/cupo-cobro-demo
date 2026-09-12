<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class FakePsp
{
    /** @var array<string, string> */
    private array $truth = [];
    /** @var array<string, int> */
    private array $settled = [];

    public function set(string $key, string $result, int $settledAmount = 0): void
    {
        $this->truth[$key] = $result;
        if ($settledAmount > 0) {
            $this->settled[$key] = $settledAmount;
        }
    }

    public function authorize(string $key): string { return $this->truth[$key] ?? 'timeout'; }
    public function lookup(string $key): string { return $this->truth[$key] ?? 'missing'; }
    public function settledAmount(string $key): ?int { return $this->settled[$key] ?? null; }
}
