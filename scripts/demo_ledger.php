<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Nucleo\Demo\Checkout;
use App\Nucleo\Demo\Closer;
use App\Nucleo\Demo\Codes;
use App\Nucleo\Demo\Detector;
use App\Nucleo\Demo\World;
use App\Nucleo\OriginTrust;

$fail = 0; $ok = 0;
function check(string $name, bool $cond): void {
    global $fail, $ok;
    if ($cond) { ++$ok; echo "OK  $name\n"; } else { ++$fail; echo "NO  $name\n"; }
}

$w = new World(); $w->slots->seed('slot-a', 1); $w->psp->set('k1', 'timeout');
$c = new Checkout($w);
$r = $c->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
check('timeout no confirma', $r['code'] === Codes::PAY_TIMEOUT && $w->ledger->status('k1') === Codes::PAY_UNKNOWN);
check('timeout mantiene hold', $w->slots->isHeld('slot-a', 'k1', 1000));
$w->psp->set('k2', 'ok');
check('segundo hold CONFLICT', $c->reserve('slot-a', 'k2', OriginTrust::fromSource('operator'), 12000, 1000)['code'] === Codes::HOLD_CONFLICT);

$w2 = new World(); $w2->slots->seed('slot-a', 1); $w2->psp->set('k1', 'reject');
$rj = (new Checkout($w2))->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
check('reject suelta hold', $rj['code'] === Codes::PAY_REJECT && !$w2->slots->isHeld('slot-a', 'k1', 1000));

$w->psp->set('k1', 'ok');
$opened = (new Detector($w))->scan(1000);
check('detector PSP_OK_LEDGER_UNKNOWN', in_array(Codes::PSP_OK_LEDGER_UNKNOWN, array_map(fn($b) => $b->code, $opened), true));
$closer = new Closer($w);
check('job no confirma', $closer->confirmUnknownAsJob('k1') === 'DETECTOR_CANNOT_CONFIRM');
$brk = null; foreach ($w->breaks as $b) { if ($b->code === Codes::PSP_OK_LEDGER_UNKNOWN) { $brk = $b; } }
check('availability no firma P2', $closer->close($brk->id, Codes::OWNER_AVAILABILITY, Codes::PSP_CONFIRMED_SIGNED) === 'WRONG_OWNER');
check('payments firma unknown→captured', $closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PSP_CONFIRMED_SIGNED) === 'OK' && $w->ledger->status('k1') === 'captured');
check('web no reembolsa', ($ref = $c->refund('k1', OriginTrust::fromSource('external_web')))['blocked'] && $ref['code'] === Codes::ORIGIN_BLOCKED);

$w3 = new World(); $w3->slots->seed('slot-a', 1); $w3->psp->set('k1', 'ok');
(new Checkout($w3))->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
$w3->ledger->markDispute('k1'); $w3->payouts->addCandidate('k1');
(new Detector($w3))->scan(1000);
$p = null; foreach ($w3->breaks as $b) { if ($b->code === Codes::PAYOUT_ON_DISPUTE) { $p = $b; } }
check('payout en disputa se nombra', $p !== null);
check('reason ilegal rechazada', (new Closer($w3))->close($p->id, Codes::OWNER_PAYMENTS, Codes::PSP_CONFIRMED_SIGNED) === 'ILLEGAL_REASON');
check('withhold saca del martes', (new Closer($w3))->close($p->id, Codes::OWNER_PAYMENTS, Codes::PAYOUT_WITHHELD) === 'OK' && !$w3->payouts->contains('k1'));

$w4 = new World(); $w4->slots->seed('slot-a', 1); $w4->psp->set('k1', 'ok');
$c4 = new Checkout($w4); $c4->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
$dup = $c4->reserve('slot-a', 'k1', OriginTrust::fromSource('operator'), 12000, 1000);
check('segundo capture NO-GO', $dup['code'] === Codes::DUPLICATE_CAPTURE_SAME_KEY && $w4->phaseNoGo);

echo "\n$ok ok / $fail fail\n";
exit($fail === 0 ? 0 : 1);
