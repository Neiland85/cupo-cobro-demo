<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class Closer
{
    public function __construct(private readonly World $world)
    {
    }

    public function close(string $breakId, string $actor, string $reason): string
    {
        $b = $this->world->breaks[$breakId] ?? null;
        if ($b === null) {
            return 'NO_SUCH_BREAK';
        }
        if ($b->state !== 'open') {
            return 'ALREADY_CLOSED';
        }
        if ($actor !== $b->owner) {
            return 'WRONG_OWNER';
        }
        $legal = $this->apply($b, $reason);
        if ($legal !== 'OK') {
            return $legal;
        }
        $b->reason = $reason;
        $b->state = $reason === Codes::PAYOUT_WITHHELD ? 'disputed' : 'matched';
        return 'OK';
    }

    private function apply(BreakRecord $b, string $reason): string
    {
        $allowed = match ($b->code) {
            Codes::PSP_OK_LEDGER_UNKNOWN => [Codes::APPLY_LATE_MATCHED, Codes::PSP_CONFIRMED_SIGNED],
            Codes::PSP_CAPTURED_CUPO_FREE => [Codes::REFUND_OVERSELL],
            Codes::CUPO_HELD_PSP_REJECT => [Codes::CART_RELEASE],
            Codes::DUPLICATE_CAPTURE_SAME_KEY => [Codes::VOID_DUPLICATE],
            Codes::SETTLEMENT_AMT_NE_LEDGER => [Codes::SETTLEMENT_ADJ],
            Codes::PAYOUT_ON_DISPUTE => [Codes::PAYOUT_WITHHELD],
            Codes::FISCAL_WITHOUT_CAPTURE => ['INVOICE_VOID'],
            Codes::CAPTURE_WITHOUT_FISCAL => ['INVOICE_RETRY'],
            Codes::AGENT_REFUND_NO_ORIGIN => [Codes::ORIGIN_BLOCK_HELD],
            Codes::LEDGER_CAPTURED_PSP_MISSING => ['PSP_DENIED_REVERSAL'],
            default => [],
        };
        if (!in_array($reason, $allowed, true)) {
            return 'ILLEGAL_REASON';
        }
        $slot = $this->world->ledger->slot($b->key) ?? '';
        switch ($reason) {
            case Codes::PSP_CONFIRMED_SIGNED:
            case Codes::APPLY_LATE_MATCHED:
                $this->world->ledger->capture($b->key);
                $this->world->fiscal->enqueue($b->key, 'inv-'.$b->key);
                break;
            case Codes::CART_RELEASE:
            case Codes::REFUND_OVERSELL:
                if ($slot !== '') {
                    $this->world->slots->release($slot, $b->key);
                }
                break;
            case Codes::PAYOUT_WITHHELD:
                $this->world->payouts->withhold($b->key);
                break;
            case 'INVOICE_VOID':
                $this->world->fiscal->void($b->key);
                break;
            case 'INVOICE_RETRY':
                $this->world->fiscal->enqueue($b->key, 'inv-'.$b->key);
                break;
        }
        return 'OK';
    }

    public function confirmUnknownAsJob(string $key): string
    {
        return 'DETECTOR_CANNOT_CONFIRM';
    }
}
