<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class Detector
{
    private int $seq = 1;

    public function __construct(private readonly World $world)
    {
    }

    /** @return list<BreakRecord> */
    public function scan(int $now): array
    {
        $opened = [];
        foreach ($this->world->ledger->keys() as $key) {
            $opened = array_merge($opened, $this->scanKey($key, $now));
        }
        return $opened;
    }

    /** @return list<BreakRecord> */
    private function scanKey(string $key, int $now): array
    {
        $out = [];
        $ledger = $this->world->ledger->status($key);
        $psp = $this->world->psp->lookup($key);
        $slotId = $this->world->ledger->slot($key) ?? '';

        if ($psp === 'ok' && $ledger === Codes::PAY_UNKNOWN) {
            $out[] = $this->open(Codes::PSP_OK_LEDGER_UNKNOWN, 'P2', $key, Codes::OWNER_PAYMENTS, ['psp' => $psp, 'ledger' => $ledger]);
        }
        if ($ledger === 'captured' && ($psp === 'missing' || $psp === 'timeout')) {
            $out[] = $this->open(Codes::LEDGER_CAPTURED_PSP_MISSING, 'P2', $key, Codes::OWNER_PAYMENTS, ['psp' => $psp]);
        }
        if ($psp === 'ok' && $ledger === 'captured' && $slotId !== '' && !$this->world->slots->isHeld($slotId, $key, $now)) {
            $out[] = $this->open(Codes::PSP_CAPTURED_CUPO_FREE, 'P1', $key, Codes::OWNER_AVAILABILITY, ['slot' => $slotId]);
        }
        if ($psp === 'reject' && $slotId !== '' && $this->world->slots->isHeld($slotId, $key, $now)) {
            $out[] = $this->open(Codes::CUPO_HELD_PSP_REJECT, 'P1', $key, Codes::OWNER_AVAILABILITY, ['slot' => $slotId]);
        }
        if ($this->world->ledger->captures($key) > 1) {
            $out[] = $this->open(Codes::DUPLICATE_CAPTURE_SAME_KEY, 'P2', $key, Codes::OWNER_PAYMENTS, ['captures' => $this->world->ledger->captures($key)]);
            $this->world->phaseNoGo = true;
        }
        $settled = $this->world->psp->settledAmount($key);
        if ($settled !== null && $ledger === 'captured' && $settled !== $this->world->ledger->amount($key)) {
            $out[] = $this->open(Codes::SETTLEMENT_AMT_NE_LEDGER, 'P3', $key, Codes::OWNER_PAYMENTS, ['ledger' => $this->world->ledger->amount($key), 'settled' => $settled]);
        }
        if ($this->world->ledger->inDispute($key) && $this->world->payouts->contains($key)) {
            $out[] = $this->open(Codes::PAYOUT_ON_DISPUTE, 'P5', $key, Codes::OWNER_PAYMENTS, []);
        }
        if ($this->world->fiscal->has($key) && $ledger !== 'captured') {
            $out[] = $this->open(Codes::FISCAL_WITHOUT_CAPTURE, 'P4', $key, Codes::OWNER_TAX, []);
        }
        if ($ledger === 'captured' && !$this->world->fiscal->has($key)) {
            $out[] = $this->open(Codes::CAPTURE_WITHOUT_FISCAL, 'P4', $key, Codes::OWNER_TAX, []);
        }
        return $out;
    }

    public function openAgentRefund(string $key): BreakRecord
    {
        return $this->open(Codes::AGENT_REFUND_NO_ORIGIN, 'Gate', $key, Codes::OWNER_PAYMENTS, []);
    }

    private function open(string $code, string $plane, string $key, string $owner, array $evidence): BreakRecord
    {
        foreach ($this->world->breaks as $existing) {
            if ($existing->code === $code && $existing->key === $key && $existing->state === 'open') {
                $existing->evidence = $evidence;
                return $existing;
            }
        }
        $b = new BreakRecord('brk-'.$this->seq++, $code, $plane, $key, $owner, 'open', null, $evidence);
        $this->world->breaks[$b->id] = $b;
        return $b;
    }
}
