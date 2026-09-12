<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Nucleo\Demo\Checkout;
use App\Nucleo\Demo\Closer;
use App\Nucleo\Demo\Codes;
use App\Nucleo\Demo\Detector;
use App\Nucleo\Demo\World;
use App\Nucleo\OriginTrust;

function line(string $t): void { echo "\n━━ $t\n"; }
function snap(World $w, int $now): void
{
    printf(
        "  t=%-5d  A{cupo=%s ledger=%-12s psp=%-7s}  B{cupo=%s ledger=%-12s psp=%-7s}\n",
        $now,
        $w->slots->isHeld('slot-a', 'A', $now) ? 'HELD' : 'FREE',
        (string) $w->ledger->status('A'),
        $w->psp->lookup('A'),
        $w->slots->isHeld('slot-a', 'B', $now) ? 'HELD' : 'FREE',
        (string) ($w->ledger->status('B') ?? '—'),
        $w->psp->lookup('B'),
    );
}

$w = new World();
$w->slots->seed('slot-a', 1);
$w->psp->set('A', 'timeout');
$w->psp->set('B', 'ok');
$c = new Checkout($w);
$op = OriginTrust::fromSource('operator');

line('1. Viajero A reserva. El PSP no contesta. Plaza HELD');
$c->reserve('slot-a', 'A', $op, 12_000, 1000);
$w->now = 1000;
snap($w, 1000);

line('2. Pasa el TTL. A pierde el hold. La plaza vuelve al cupo');
$w->now = 2000;
snap($w, 2000);

line('3. Viajero B reserva la misma plaza. PSP OK. Captured');
$c->reserve('slot-a', 'B', $op, 12_000, 2000);
snap($w, 2000);

line('4. Llega tarde el OK del PSP de A. El asiento ya es de B');
$w->psp->set('A', 'ok');
snap($w, 2000);

line('5. Detector: LATE_PSP_NO_CUPO — no PSP_OK_LEDGER_UNKNOWN');
(new Detector($w))->scan(2000);
$brk = null;
foreach ($w->breaks as $b) {
    if ($b->code === Codes::LATE_PSP_NO_CUPO) {
        $brk = $b;
    }
}
echo '  break='.($brk?->code ?? '—')."\n";

line('6. Payments intenta confirmar a A sobre la plaza — NO_CUPO / ilegal');
$closer = new Closer($w);
echo '  confirm='.$closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PSP_CONFIRMED_SIGNED)."\n";
snap($w, 2000);

line('7. Payments reembolsa a A. B se queda la plaza');
echo '  refund='.$closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::REFUND_OVERSELL)."\n";
snap($w, 2000);

echo "\nlisto.\n";
