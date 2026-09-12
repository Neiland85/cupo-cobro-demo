<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Nucleo\Demo\Checkout;
use App\Nucleo\Demo\Codes;
use App\Nucleo\Demo\Detector;
use App\Nucleo\Demo\World;
use App\Nucleo\OriginTrust;

function line(string $t): void { echo "\n━━ $t\n"; }

$w = new World();
$w->slots->seed('slot-a', 1);
$w->psp->set('k1', 'ok');
$c = new Checkout($w);
$op = OriginTrust::fromSource('operator');

line('1. Primer checkout. Misma Idempotency-Key k1');
$r1 = $c->reserve('slot-a', 'k1', $op, 12_000, 1000);
echo '  code='.$r1['code'].'  ledger='.$w->ledger->status('k1').'  captures='.$w->ledger->captures('k1')."\n";

line('2. El cliente reintenta. El webhook del PSP llega otra vez. Misma key');
$r2 = $c->reserve('slot-a', 'k1', $op, 12_000, 1000);
echo '  code='.$r2['code'].'  blocked='.($r2['blocked'] ? 'yes' : 'no')."\n";
echo '  captures='.$w->ledger->captures('k1').'  phaseNoGo='.($w->phaseNoGo ? 'YES' : 'no')."\n";

line('3. Detector nombra DUPLICATE_CAPTURE_SAME_KEY — no hay segundo asiento');
(new Detector($w))->scan(1000);
foreach ($w->breaks as $b) {
    if ($b->code === Codes::DUPLICATE_CAPTURE_SAME_KEY) {
        echo '  break='.$b->code.' owner='.$b->owner."\n";
    }
}
echo '  cupo Aún HELD para k1: '.($w->slots->isHeld('slot-a', 'k1', 1000) ? 'yes' : 'no')."\n";

echo "\nlisto.\n";
