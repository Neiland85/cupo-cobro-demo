<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class PayoutBook
{
    /** @var list<string> */
    public array $file = [];
    /** @var list<string> */
    public array $withheld = [];

    public function addCandidate(string $key): void
    {
        $this->file[] = $key;
    }

    public function withhold(string $key): void
    {
        $this->file = array_values(array_filter($this->file, static fn (string $k): bool => $k !== $key));
        $this->withheld[] = $key;
    }

    public function contains(string $key): bool
    {
        return in_array($key, $this->file, true);
    }
}
