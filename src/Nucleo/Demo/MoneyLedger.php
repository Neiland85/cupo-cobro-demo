<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class MoneyLedger
{
    /** @var array<string, array{status:string, amount:int, slot:string, captures:int, dispute:bool, refunded:bool}> */
    private array $rows = [];

    public function openUnknown(string $key, string $slot, int $amount): void
    {
        if (isset($this->rows[$key])) {
            return;
        }
        $this->rows[$key] = ['status' => Codes::PAY_UNKNOWN, 'amount' => $amount, 'slot' => $slot, 'captures' => 0, 'dispute' => false, 'refunded' => false];
    }

    public function capture(string $key): string
    {
        $row = $this->rows[$key] ?? null;
        if ($row === null) {
            $this->rows[$key] = ['status' => 'captured', 'amount' => 0, 'slot' => '', 'captures' => 1, 'dispute' => false, 'refunded' => false];
            return Codes::PAY_OK;
        }
        ++$this->rows[$key]['captures'];
        if ($this->rows[$key]['captures'] > 1 && $row['status'] === 'captured') {
            return Codes::DUPLICATE_CAPTURE_SAME_KEY;
        }
        $this->rows[$key]['status'] = 'captured';
        return Codes::PAY_OK;
    }

    public function reject(string $key): void
    {
        if (isset($this->rows[$key])) {
            $this->rows[$key]['status'] = Codes::PAY_REJECT;
        }
    }

    public function markDispute(string $key): void
    {
        if (isset($this->rows[$key])) {
            $this->rows[$key]['dispute'] = true;
        }
    }

    public function markRefunded(string $key): void
    {
        if (isset($this->rows[$key])) {
            $this->rows[$key]['refunded'] = true;
            $this->rows[$key]['status'] = 'refunded';
        }
    }

    public function isRefunded(string $key): bool { return $this->rows[$key]['refunded'] ?? false; }
    public function status(string $key): ?string { return $this->rows[$key]['status'] ?? null; }
    public function slot(string $key): ?string { return $this->rows[$key]['slot'] ?? null; }
    public function amount(string $key): int { return $this->rows[$key]['amount'] ?? 0; }
    public function captures(string $key): int { return $this->rows[$key]['captures'] ?? 0; }
    public function inDispute(string $key): bool { return $this->rows[$key]['dispute'] ?? false; }
    /** @return list<string> */
    public function keys(): array { return array_keys($this->rows); }
}
