<?php

declare(strict_types=1);

namespace App\Command;

use App\Nucleo\Demo\Checkout;
use App\Nucleo\Demo\Closer;
use App\Nucleo\Demo\Codes;
use App\Nucleo\Demo\Detector;
use App\Nucleo\Demo\World;
use App\Nucleo\OriginTrust;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nucleo:demo',
    description: 'Banco de ensayo fail-closed: refund|unknown|oversell|replay',
)]
final class NucleoDemoCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('scene', InputArgument::REQUIRED, 'refund | unknown | oversell | replay');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $scene = (string) $input->getArgument('scene');

        return match ($scene) {
            'refund' => $this->refund($io),
            'unknown' => $this->unknown($io),
            'oversell' => $this->oversell($io),
            'replay' => $this->replay($io),
            default => $this->unknownScene($io, $scene),
        };
    }

    private function unknownScene(SymfonyStyle $io, string $scene): int
    {
        $io->error('escena desconocida: '.$scene.' — usa refund|unknown|oversell|replay');

        return Command::FAILURE;
    }

    private function refund(SymfonyStyle $io): int
    {
        $io->title('refund vs payout (martes)');
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'ok');
        $c = new Checkout($w);
        $op = OriginTrust::fromSource('operator');
        $c->reserve('slot-a', 'k1', $op, 12_000, 1000);
        $w->payouts->addCandidate('k1');
        $first = $c->refund('k1', $op);
        $io->writeln('1. refund con fichero: '.$first['code']);
        (new Detector($w))->scan(1000);
        $brk = $this->breakOf($w, Codes::PAYOUT_ON_DISPUTE);
        $io->writeln('2. detector: '.($brk?->code ?? '—'));
        $io->writeln('3. withhold: '.(new Closer($w))->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PAYOUT_WITHHELD));
        $second = $c->refund('k1', $op);
        $io->writeln('4. refund tras withhold: '.$second['code']);
        $io->success('cupo='.($w->slots->isHeld('slot-a', 'k1', 1000) ? 'HELD' : 'FREE').' ledger='.$w->ledger->status('k1'));

        return Command::SUCCESS;
    }

    private function unknown(SymfonyStyle $io): int
    {
        $io->title('timeout / unknown');
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'timeout');
        $c = new Checkout($w);
        $op = OriginTrust::fromSource('operator');
        $io->writeln('1. reserve: '.$c->reserve('slot-a', 'k1', $op, 12_000, 1000)['code']);
        $io->writeln('2. refund unknown: '.$c->refund('k1', $op)['code']);
        $w->psp->set('k1', 'ok');
        (new Detector($w))->scan(1000);
        $brk = $this->breakOf($w, Codes::PSP_OK_LEDGER_UNKNOWN);
        $closer = new Closer($w);
        $io->writeln('3. job: '.$closer->confirmUnknownAsJob('k1'));
        $io->writeln('4. availability: '.$closer->close($brk->id, Codes::OWNER_AVAILABILITY, Codes::PSP_CONFIRMED_SIGNED));
        $io->writeln('5. payments: '.$closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PSP_CONFIRMED_SIGNED));
        $io->success('ledger='.$w->ledger->status('k1'));

        return Command::SUCCESS;
    }

    private function oversell(SymfonyStyle $io): int
    {
        $io->title('late PSP / oversell');
        $w = new World();
        $w->now = 2000;
        $w->slots->seed('slot-a', 1);
        $w->psp->set('A', 'timeout');
        $w->psp->set('B', 'ok');
        $c = new Checkout($w);
        $op = OriginTrust::fromSource('operator');
        $c->reserve('slot-a', 'A', $op, 12_000, 1000);
        $c->reserve('slot-a', 'B', $op, 12_000, 2000);
        $w->psp->set('A', 'ok');
        (new Detector($w))->scan(2000);
        $brk = $this->breakOf($w, Codes::LATE_PSP_NO_CUPO);
        $closer = new Closer($w);
        $io->writeln('1. confirm A: '.$closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::PSP_CONFIRMED_SIGNED));
        $io->writeln('2. refund A: '.$closer->close($brk->id, Codes::OWNER_PAYMENTS, Codes::REFUND_OVERSELL));
        $io->success('A='.$w->ledger->status('A').' B='.$w->ledger->status('B').' cupoB='.($w->slots->isHeld('slot-a', 'B', 2000) ? 'HELD' : 'FREE'));

        return Command::SUCCESS;
    }

    private function replay(SymfonyStyle $io): int
    {
        $io->title('idempotency replay');
        $w = new World();
        $w->slots->seed('slot-a', 1);
        $w->psp->set('k1', 'ok');
        $c = new Checkout($w);
        $op = OriginTrust::fromSource('operator');
        $io->writeln('1. '.$c->reserve('slot-a', 'k1', $op, 12_000, 1000)['code'].' captures='.$w->ledger->captures('k1'));
        $r2 = $c->reserve('slot-a', 'k1', $op, 12_000, 1000);
        $io->writeln('2. '.$r2['code'].' captures='.$w->ledger->captures('k1').' nogo='.($w->phaseNoGo ? 'YES' : 'no'));
        $io->success('una key, un asiento, segundo acto = NO-GO');

        return Command::SUCCESS;
    }

    private function breakOf(World $w, string $code): ?\App\Nucleo\Demo\BreakRecord
    {
        foreach ($w->breaks as $b) {
            if ($b->code === $code) {
                return $b;
            }
        }

        return null;
    }
}
