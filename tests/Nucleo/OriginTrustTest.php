<?php

declare(strict_types=1);

namespace App\Tests\Nucleo;

use App\Nucleo\HoldRunner;
use App\Nucleo\OriginTrust;
use App\Nucleo\PaymentAdapter;
use Clarity\Nucleo\HoldPipe\Pipe;
use PHPUnit\Framework\TestCase;

final class OriginTrustTest extends TestCase
{
    public function test_external_web_is_never_trusted(): void
    {
        $origin = OriginTrust::fromSource('external_web');

        self::assertSame('external_web', $origin->provenance);
        self::assertSame('untrusted', $origin->trust);
        self::assertFalse($origin->mayInstruct());
    }

    public function test_rag_email_and_upload_are_data(): void
    {
        foreach (['rag', 'email', 'upload', 'retrieved', ''] as $source) {
            $origin = OriginTrust::fromSource($source);
            self::assertSame('untrusted', $origin->trust, $source);
            self::assertFalse($origin->mayInstruct(), $source);
        }
    }

    public function test_only_operator_may_instruct(): void
    {
        $origin = OriginTrust::fromSource('operator');

        self::assertSame('operator', $origin->provenance);
        self::assertSame('trusted', $origin->trust);
        self::assertTrue($origin->mayInstruct());
    }

    public function test_web_refund_is_blocked_before_capability(): void
    {
        $runner = new HoldRunner(new Pipe(), new PaymentAdapter('ok'));
        $origin = OriginTrust::fromSource('external_web');
        $r = $runner->run('rental.supervisor', 'RefundPayment', 'web-001', '2026-10-25', $origin);

        self::assertTrue($r['blocked']);
        self::assertNull($r['reservation']);
        self::assertSame('untrusted-content', $r['evidence']->policy);
        self::assertSame('DENIED', $r['evidence']->authorization);

        $ids = array_column($r['steps'], 'id');
        self::assertContains('boundary', $ids);
        self::assertNotContains('capability', $ids);
    }
}
