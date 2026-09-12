<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Tests;

use PHPUnit\Framework\TestCase;

final class BoundariesTest extends TestCase
{
    private const CONTEXTS = ['Catalog', 'Availability', 'Booking', 'Payment', 'Supplier', 'Identity'];

    public function test_a_context_does_not_import_another_context(): void
    {
        $dir = dirname(__DIR__).'/src/Context';
        foreach (self::CONTEXTS as $name) {
            $src = file_get_contents($dir.'/'.$name.'.php');
            self::assertNotFalse($src);
            foreach (self::CONTEXTS as $other) {
                if ($other === $name) {
                    continue;
                }
                self::assertStringNotContainsString(
                    'use Clarity\\Nucleo\\Context\\'.$other,
                    $src,
                    $name.' no puede importar '.$other,
                );
            }
        }
    }

    public function test_domain_does_not_name_infrastructure(): void
    {
        $root = dirname(__DIR__).'/src';
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            if (str_contains($file->getPathname(), '/HoldPipe/PaymentStub.php')) {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            self::assertNotFalse($src);
            self::assertDoesNotMatchRegularExpression('/\bStripe\b/', $src, $file->getFilename());
            self::assertDoesNotMatchRegularExpression('/\bPDO\b/', $src, $file->getFilename());
            self::assertDoesNotMatchRegularExpression('/\bHttpRequest\b/', $src, $file->getFilename());
        }
    }

    public function test_payment_port_is_the_only_extension_point_for_money(): void
    {
        $port = file_get_contents(dirname(__DIR__).'/src/HoldPipe/PaymentPort.php');
        self::assertNotFalse($port);
        self::assertStringContainsString('interface PaymentPort', $port);
        self::assertStringContainsString("return 'ok'|'reject'|'timeout'", $port);
    }
}
