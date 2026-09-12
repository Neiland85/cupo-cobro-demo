<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Tests;

use Clarity\Nucleo\Evidence\Chain;
use Clarity\Nucleo\Evidence\Hash;
use Clarity\Nucleo\Evidence\Record;
use PHPUnit\Framework\TestCase;

final class ChainTest extends TestCase
{
    public function test_entries_link_to_genesis_and_to_each_other(): void
    {
        $chain = new Chain();
        $a = new Record('ev-a', 1, 'rental.supervisor', 'HoldAvailability', 'HoldAvailability', 'hold-and-pay', 'ALLOW', 'confirmed rsv-001', Hash::of('a'));
        $b = new Record('ev-b', 2, 'rental.supervisor', 'HoldAvailability', 'HoldAvailability', 'idempotency', 'ALLOW', 'replay rsv-001', Hash::of('b'));
        $first = $chain->append($a, 'happy');
        $second = $chain->append($b, 'replay');

        self::assertSame(Chain::GENESIS, $first['prev']);
        self::assertSame($first['hash'], $second['prev']);
        self::assertTrue($chain->verify());
    }
}
