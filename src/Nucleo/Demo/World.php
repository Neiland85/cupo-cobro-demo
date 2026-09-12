<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class World
{
    public SlotStore $slots;
    public MoneyLedger $ledger;
    public FakePsp $psp;
    public FiscalBook $fiscal;
    public PayoutBook $payouts;
    /** @var array<string, BreakRecord> */
    public array $breaks = [];
    public bool $phaseNoGo = false;
    public int $now = 1000;

    public function __construct()
    {
        $this->slots = new SlotStore();
        $this->ledger = new MoneyLedger();
        $this->psp = new FakePsp();
        $this->fiscal = new FiscalBook();
        $this->payouts = new PayoutBook();
    }
}
