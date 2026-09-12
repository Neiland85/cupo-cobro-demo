<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Nucleo\Demo\Checkout;
use App\Nucleo\Demo\Closer;
use App\Nucleo\Demo\Codes;
use App\Nucleo\Demo\Detector;
use App\Nucleo\Demo\World;
use App\Nucleo\OriginTrust;

function line(string $title): void
{
    echo "\n━━ ".$title."\n";
}

function snap(World $w, string $key, int $now): void
{
    $slot = $w->ledger->slot($key) ?? 'slot-a';
    $cupo = $w->slots->isHeld($slot, $key, $now) ? 'HELD' : 'FREE';
    $pay = $w->payouts->contains($key) ? 'IN_FILE' : (in_array($key, $w->payouts->withheld, true) ? 'WITHHELD' : '—');
    $breaks = [];
    foreach ($w->breaks as $b) {
        if ($b->key === $key) {
            $breaks[] = $b->code.'@'.$b->state;
        }
    }
    printf(
        "  cupo=%-5s  ledger=%-12s  payout=%-8s  refunded=%s  breaks=%s\n",
        $cupo,
        (string) $w->ledger->status($key),
        $pay,
        $w->ledger->isRefunded($key) ? 'yes' : 'no',
        $breaks === [] ? '—' : implode(',', $breaks),
    );
}

$w = new World();
$w->slots->seed('slot-a', 1);
$w->psp->set('k1', 'ok');
$c = new Checkout($w);
$op = OriginTrust::fromSource('operator');
$web = OriginTrust::fromSource('external_web');
$now = 1_000;

line('1. Capture — viajero cobrado, plaza cogida');
$c->reserve('slot-a', 'k1', $op, 12_000, $now);
snap($w, 'k1', $now);

line('2. El martes: la key entra en el fichero de payout al proveedor');
$w->payouts->addCandidate('k1');
snap($w, 'k1', $now);

line('3. La web intenta reembolsar — origen bloqueado');
$r = $c->refund('k1', $web);
echo '  code='.$r['code'].' blocked='.($r['blocked'] ? 'yes' : 'no')."\n";
snap($w, 'k1', $now);

line('4. Operator refund con payout en fichero — NO suelta cupo');
$r = $c->refund('k1', $op);
echo '  code='.$r['code'].' blocked='.($r['blocked'] ? 'yes' : 'no')."\n";
snap($w, 'k1', $now);

line('5. Detector nombra PAYOUT_ON_DISPUTE — no mueve dinero');
(new Detector($w))->scan($now);
snap($w, 'k1', $now);

line('6. Payments firma withhold — sale del martes');
$brk = null;
foreach ($w->breaks as $b) {
    if ($b->code === Codes::PAYOUT_ON_DISPUTE) {
        $brk = $b;
    }
}
echo '  closer='.(new Closer($w))->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PAYOUT_WITHHELD)."\n";
snap($w, 'k1', $now);

line('7. Segundo refund — ahora sí viajero + cupo');
$r = $c->refund('k1', $op);
echo '  code='.$r['code'].' blocked='.($r['blocked'] ? 'yes' : 'no')."\n";
snap($w, 'k1', $now);

line('8. Replay — no hay segundo movimiento');
$r = $c->refund('k1', $op);
echo '  code='.$r['code']."\n";
snap($w, 'k1', $now);

echo "\nlisto.\n";
