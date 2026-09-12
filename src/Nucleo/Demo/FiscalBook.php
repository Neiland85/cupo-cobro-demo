<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class FiscalBook
{
    /** @var array<string, string> */
    private array $entries = [];

    public function enqueue(string $key, string $invoiceKey): void
    {
        $this->entries[$key] = $invoiceKey;
    }

    public function void(string $key): void
    {
        $this->entries[$key] = 'void';
    }

    public function has(string $key): bool
    {
        return isset($this->entries[$key]) && $this->entries[$key] !== 'void';
    }
}
