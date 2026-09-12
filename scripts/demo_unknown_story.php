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
    printf(
        "  cupo=%-5s  ledger=%-22s  psp=%-8s\n",
        $cupo,
        (string) $w->ledger->status($key),
        $w->psp->lookup($key),
    );
}

$w = new World();
$w->slots->seed('slot-a', 1);
$w->psp->set('k1', 'timeout');
$c = new Checkout($w);
$op = OriginTrust::fromSource('operator');
$now = 1_000;

line('1. Checkout — el PSP no contesta');
$r = $c->reserve('slot-a', 'k1', $op, 12_000, $now);
echo '  reserve='.$r['code']."\n";
snap($w, 'k1', $now);

line('2. Operator quiere refund — no hay cobro casado');
$r = $c->refund('k1', $op);
echo '  refund='.$r['code']."\n";
snap($w, 'k1', $now);

line('3. El PSP aparece: cobró. El ledger sigue unknown');
$w->psp->set('k1', 'ok');
snap($w, 'k1', $now);

line('4. Detector nombra el break — no confirma');
(new Detector($w))->scan($now);
$brk = null;
foreach ($w->breaks as $b) {
    if ($b->code === Codes::PSP_OK_LEDGER_UNKNOWN) {
        $brk = $b;
    }
}
echo '  break='.($brk?->code ?? '—')."\n";
echo '  job='.(new Closer($w))->confirmUnknownAsJob('k1')."\n";

line('5. Availability intenta firmar — dueño incorrecto');
echo '  avail='.(new Closer($w))->close($brk->id, Codes::OWNER_AVAILABILITY, Codes::PSP_CONFIRMED_SIGNED)."\n";
snap($w, 'k1', $now);

line('6. Payments firma — unknown pasa a captured');
echo '  pay='.(new Closer($w))->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PSP_CONFIRMED_SIGNED)."\n";
snap($w, 'k1', $now);

line('7. Ahora sí se puede reembolsar (no hay payout en fichero)');
$r = $c->refund('k1', $op);
echo '  refund='.$r['code']."\n";
snap($w, 'k1', $now);

echo "\nlisto.\n";
