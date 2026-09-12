<?php

declare(strict_types=1);

namespace App\Tests\Nucleo;

use Clarity\Nucleo\Capability\Gate;
use Clarity\Nucleo\Content\Boundary;
use PHPUnit\Framework\TestCase;

final class RequireKernelTest extends TestCase
{
    public function test_untrusted_web_cannot_instruct(): void
    {
        $decision = (new Boundary())->admit('external_web', 'untrusted');
        self::assertFalse($decision->allowed);
        self::assertSame('DENIED', $decision->authorization);
        self::assertSame('untrusted-content', $decision->policy);
    }

    public function test_rental_supervisor_cannot_refund(): void
    {
        $decision = (new Gate())->decide('rental.supervisor', 'RefundPayment');
        self::assertFalse($decision->allowed);
        self::assertSame('DENIED', $decision->authorization);
    }

    public function test_product_src_does_not_absorb_kernel_contexts(): void
    {
        $root = dirname(__DIR__, 2).'/src';
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            self::assertNotFalse($src);
            self::assertStringNotContainsString(
                'namespace Clarity\\Nucleo\\Context',
                $src,
                $file->getFilename().' absorbe un contexto del kernel',
            );
        }
    }
}
