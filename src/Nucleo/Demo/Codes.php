<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class Codes
{
    public const PAY_OK = 'PAY_OK';
    public const PAY_REJECT = 'PAY_REJECT';
    public const PAY_TIMEOUT = 'PAY_TIMEOUT';
    public const PAY_UNKNOWN = 'PAY_UNKNOWN';

    public const HOLD_OK = 'HOLD_OK';
    public const HOLD_CONFLICT = 'HOLD_CONFLICT';
    public const HOLD_EXPIRED = 'HOLD_EXPIRED';
    public const HOLD_REPLAY = 'HOLD_REPLAY';
    public const SLOT_FULL = 'SLOT_FULL';

    public const ORIGIN_BLOCKED = 'ORIGIN_BLOCKED';

    public const PSP_OK_LEDGER_UNKNOWN = 'PSP_OK_LEDGER_UNKNOWN';
    public const LEDGER_CAPTURED_PSP_MISSING = 'LEDGER_CAPTURED_PSP_MISSING';
    public const PSP_CAPTURED_CUPO_FREE = 'PSP_CAPTURED_CUPO_FREE';
    public const CUPO_HELD_PSP_REJECT = 'CUPO_HELD_PSP_REJECT';
    public const DUPLICATE_CAPTURE_SAME_KEY = 'DUPLICATE_CAPTURE_SAME_KEY';
    public const SETTLEMENT_AMT_NE_LEDGER = 'SETTLEMENT_AMT_NE_LEDGER';
    public const PAYOUT_ON_DISPUTE = 'PAYOUT_ON_DISPUTE';
    public const FISCAL_WITHOUT_CAPTURE = 'FISCAL_WITHOUT_CAPTURE';
    public const CAPTURE_WITHOUT_FISCAL = 'CAPTURE_WITHOUT_FISCAL';
    public const AGENT_REFUND_NO_ORIGIN = 'AGENT_REFUND_NO_ORIGIN';

    public const APPLY_LATE_MATCHED = 'APPLY_LATE_MATCHED';
    public const PSP_CONFIRMED_SIGNED = 'PSP_CONFIRMED_SIGNED';
    public const REFUND_OVERSELL = 'REFUND_OVERSELL';
    public const CART_RELEASE = 'CART_RELEASE';
    public const VOID_DUPLICATE = 'VOID_DUPLICATE';
    public const SETTLEMENT_ADJ = 'SETTLEMENT_ADJ';
    public const PAYOUT_WITHHELD = 'PAYOUT_WITHHELD';
    public const ORIGIN_BLOCK_HELD = 'ORIGIN_BLOCK_HELD';

    public const OWNER_PAYMENTS = 'payments';
    public const OWNER_AVAILABILITY = 'availability';
    public const OWNER_TAX = 'tax';
}
