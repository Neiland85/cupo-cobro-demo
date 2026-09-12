<?php

declare(strict_types=1);

namespace App\Tests\Nucleo;

use App\Nucleo\Demo\Checkout;
use App\Nucleo\Demo\Closer;
use App\Nucleo\Demo\Codes;
use App\Nucleo\Demo\Detector;
use App\Nucleo\Demo\World;
use App\Nucleo\OriginTrust;
use PHPUnit\Framework\TestCase;

final class DemoLedgerTest extends TestCase
{
    public function test_timeout_does_not_confirm_and_keeps_hold(): void
    {
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'timeout');
        $r = (new Checkout($w))->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);

        self::assertSame(Codes::PAY_TIMEOUT, $r['code']);
        self::assertSame(Codes::PAY_UNKNOWN, $w->ledger->status('k1'));
        self::assertTrue($w->slots->isHeld('slot-a', 'k1', 1000));
    }

    public function test_second_hold_on_same_slot_conflicts(): void
    {
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'timeout');
        $w->psp->set('k2', 'ok');
        $c = new Checkout($w);
        $c->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
        $r = $c->reserve('slot-a', 'k2', OriginTrust::fromSource('operator'), 12000, 1000);

        self::assertSame(Codes::HOLD_CONFLICT, $r['code']);
        self::assertTrue($r['blocked']);
    }

    public function test_reject_releases_hold(): void
    {
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'reject');
        $r = (new Checkout($w))->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);

        self::assertSame(Codes::PAY_REJECT, $r['code']);
        self::assertFalse($w->slots->isHeld('slot-a', 'k1', 1000));
    }

    public function test_detector_names_unknown_and_only_payments_confirms(): void
    {
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'timeout');
        (new Checkout($w))->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
        $w->psp->set('k1', 'ok');

        $opened = (new Detector($w))->scan(1000);
        $codes = array_map(static fn ($b) => $b->code, $opened);
        self::assertContains(Codes::PSP_OK_LEDGER_UNKNOWN, $codes);
        self::assertSame(Codes::PAY_UNKNOWN, $w->ledger->status('k1'));

        $closer = new Closer($w);
        self::assertSame('DETECTOR_CANNOT_CONFIRM', $closer->confirmUnknownAsJob('k1'));

        $brk = null;
        foreach ($w->breaks as $b) {
            if ($b->code === Codes::PSP_OK_LEDGER_UNKNOWN) {
                $brk = $b;
            }
        }
        self::assertNotNull($brk);
        self::assertSame('WRONG_OWNER', $closer->close($brk->id, Codes::OWNER_AVAILABILITY, Codes::PSP_CONFIRMED_SIGNED));
        self::assertSame('OK', $closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PSP_CONFIRMED_SIGNED));
        self::assertSame('captured', $w->ledger->status('k1'));
        self::assertSame('matched', $brk->state);
    }

    public function test_external_web_cannot_refund(): void
    {
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'ok');
        $c = new Checkout($w);
        $c->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
        $r = $c->refund('k1', OriginTrust::fromSource('external_web'));

        self::assertTrue($r['blocked']);
        self::assertSame(Codes::ORIGIN_BLOCKED, $r['code']);
        $b = (new Detector($w))->openAgentRefund('k1');
        self::assertSame(Codes::AGENT_REFUND_NO_ORIGIN, $b->code);
        self::assertSame('open', $b->state);
    }

    public function test_payout_on_dispute_is_withheld(): void
    {
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'ok');
        (new Checkout($w))->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
        $w->ledger->markDispute('k1');
        $w->payouts->addCandidate('k1');

        (new Detector($w))->scan(1000);
        $brk = null;
        foreach ($w->breaks as $b) {
            if ($b->code === Codes::PAYOUT_ON_DISPUTE) {
                $brk = $b;
            }
        }
        self::assertNotNull($brk);
        $closer = new Closer($w);
        self::assertSame('ILLEGAL_REASON', $closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PSP_CONFIRMED_SIGNED));
        self::assertSame('OK', $closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PAYOUT_WITHHELD));
        self::assertFalse($w->payouts->contains('k1'));
        self::assertContains('k1', $w->payouts->withheld);
        self::assertSame('disputed', $brk->state);
    }

    public function test_duplicate_capture_sets_phase_nogo(): void
    {
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'ok');
        $c = new Checkout($w);
        $c->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
        $again = $c->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);

        self::assertSame(Codes::DUPLICATE_CAPTURE_SAME_KEY, $again['code']);
        self::assertTrue($w->phaseNoGo);
    }

    public function test_settlement_adjusts_amount_not_status(): void
    {
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'ok', 11880);
        (new Checkout($w))->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
        $opened = (new Detector($w))->scan(1000);
        $codes = array_map(static fn ($b) => $b->code, $opened);
        self::assertContains(Codes::SETTLEMENT_AMT_NE_LEDGER, $codes);
        self::assertSame('captured', $w->ledger->status('k1'));
    }
}
