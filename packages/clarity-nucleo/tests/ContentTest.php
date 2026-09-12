<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Tests;

use Clarity\Nucleo\Content\Boundary;
use PHPUnit\Framework\TestCase;

final class ContentTest extends TestCase
{
    public function test_external_web_cannot_instruct(): void
    {
        $decision = (new Boundary())->admit('external_web', 'untrusted');
        self::assertFalse($decision->allowed);
        self::assertSame('DENIED', $decision->authorization);
        self::assertSame('untrusted-content', $decision->policy);
    }

    public function test_operator_trusted_may_instruct(): void
    {
        $decision = (new Boundary())->admit('operator', 'trusted');
        self::assertTrue($decision->allowed);
        self::assertSame('ALLOW', $decision->authorization);
    }
}
