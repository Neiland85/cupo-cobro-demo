<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Tests;

use Clarity\Nucleo\HoldPipe\PaymentStub;
use Clarity\Nucleo\HoldPipe\Pipe;
use PHPUnit\Framework\TestCase;

final class PipeTest extends TestCase
{
    public function test_happy_path_confirms_and_replay_keeps_the_same_id(): void
    {
        $pipe = new Pipe();
        $first = $pipe->run('happy', 'rental.supervisor', 'HoldAvailability', 'demo-001', '2026-10-10', new PaymentStub('ok'));
        self::assertFalse($first['blocked']);
        self::assertSame('confirmed', $first['reservation']?->status);

        $again = $pipe->run('replay', 'rental.supervisor', 'HoldAvailability', 'demo-001', '2026-10-10', new PaymentStub('ok'));
        self::assertTrue($again['reservation']?->replay);
        self::assertSame($first['reservation']?->id, $again['reservation']?->id);
    }

    public function test_timeout_is_unknown_not_confirmed(): void
    {
        $pipe = new Pipe();
        $r = $pipe->run('timeout', 'payment.agent', 'AuthorizePayment', 'demo-003', '2026-10-15', new PaymentStub('timeout'));
        self::assertSame('unknown', $r['reservation']?->status);
        self::assertNotSame('confirmed', $r['reservation']?->status);
    }

    public function test_compromised_agent_does_not_touch_the_domain(): void
    {
        $pipe = new Pipe();
        $r = $pipe->run('compromised', 'rental.supervisor', 'CapturePayment', 'demo-004', '2026-10-20', new PaymentStub('ok'));
        self::assertTrue($r['blocked']);
        self::assertNull($r['reservation']);
        self::assertSame('DENIED', $r['evidence']->authorization);
    }

    public function test_subagent_is_blocked_at_delegation(): void
    {
        $pipe = new Pipe();
        $r = $pipe->run(
            'subagent',
            'rental.child',
            'CapturePayment',
            'demo-005',
            '2026-10-22',
            new PaymentStub('ok'),
            'HoldAvailability',
        );
        self::assertTrue($r['blocked']);
        self::assertNull($r['reservation']);
        self::assertSame('delegation', $r['steps'][1]['id']);
        self::assertSame('block', $r['steps'][1]['verdict']);
        self::assertFalse($this->hasStep($r['steps'], 'capability'));
    }

    public function test_external_web_is_blocked_at_content_boundary(): void
    {
        $pipe = new Pipe();
        $r = $pipe->run(
            'injection',
            'rental.supervisor',
            'RefundPayment',
            'demo-006',
            '2026-10-25',
            new PaymentStub('ok'),
            null,
            'external_web',
            'untrusted',
        );
        self::assertTrue($r['blocked']);
        self::assertNull($r['reservation']);
        self::assertSame('untrusted-content', $r['evidence']->policy);
        self::assertSame('DENIED', $r['evidence']->authorization);
        self::assertTrue($this->hasStep($r['steps'], 'boundary'));
        self::assertFalse($this->hasStep($r['steps'], 'capability'));
        self::assertStringContainsString('no es la frontera', $r['message']);
    }

    /** @param list<array{id:string,label:string,verdict:string,detail:string}> $steps */
    private function hasStep(array $steps, string $id): bool
    {
        foreach ($steps as $step) {
            if ($step['id'] === $id) {
                return true;
            }
        }

        return false;
    }
}
