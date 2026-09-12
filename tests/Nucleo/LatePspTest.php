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

final class LatePspTest extends TestCase
{
    public function test_late_psp_ok_does_not_steal_seat_from_second_traveler(): void
    {
        $w = new World();
        $w->now = 1000;
        $w->slots->seed('slot-a', 1);
        $w->psp->set('A', 'timeout');
        $w->psp->set('B', 'ok');
        $c = new Checkout($w);
        $op = OriginTrust::fromSource('operator');

        $c->reserve('slot-a', 'A', $op, 12_000, 1000);
        self::assertSame(Codes::PAY_UNKNOWN, $w->ledger->status('A'));
        self::assertTrue($w->slots->isHeld('slot-a', 'A', 1000));

        $w->now = 2000;
        self::assertFalse($w->slots->isHeld('slot-a', 'A', 2000));

        $b = $c->reserve('slot-a', 'B', $op, 12_000, 2000);
        self::assertSame(Codes::PAY_OK, $b['code']);
        self::assertSame('captured', $w->ledger->status('B'));
        self::assertTrue($w->slots->isHeld('slot-a', 'B', 2000));

        $w->psp->set('A', 'ok');
        $opened = (new Detector($w))->scan(2000);
        $codes = array_map(static fn ($x) => $x->code, $opened);
        self::assertContains(Codes::LATE_PSP_NO_CUPO, $codes);
        self::assertNotContains(Codes::PSP_OK_LEDGER_UNKNOWN, $codes);

        $brk = null;
        foreach ($w->breaks as $row) {
            if ($row->code === Codes::LATE_PSP_NO_CUPO) {
                $brk = $row;
            }
        }
        self::assertNotNull($brk);
        $closer = new Closer($w);
        self::assertSame('ILLEGAL_REASON', $closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PSP_CONFIRMED_SIGNED));
        self::assertSame('OK', $closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::REFUND_OVERSELL));
        self::assertTrue($w->ledger->isRefunded('A'));
        self::assertSame('captured', $w->ledger->status('B'));
        self::assertTrue($w->slots->isHeld('slot-a', 'B', 2000));
    }
}
