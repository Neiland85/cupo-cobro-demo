<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

use App\Nucleo\OriginTrust;

final class Checkout
{
    public function __construct(private readonly World $world)
    {
    }

    /** @return array{code:string, blocked:bool, status:?string} */
    public function reserve(string $slotId, string $key, OriginTrust $origin, int $amount, int $now): array
    {
        $hold = $this->world->slots->hold($slotId, $key, $now);
        if ($hold !== Codes::HOLD_OK && $hold !== Codes::HOLD_REPLAY) {
            return ['code' => $hold, 'blocked' => true, 'status' => null];
        }

        $this->world->ledger->openUnknown($key, $slotId, $amount);
        $psp = $this->world->psp->authorize($key);

        if ($psp === 'reject') {
            $this->world->ledger->reject($key);
            $this->world->slots->release($slotId, $key);
            return ['code' => Codes::PAY_REJECT, 'blocked' => false, 'status' => Codes::PAY_REJECT];
        }

        if ($psp === 'ok') {
            $cap = $this->world->ledger->capture($key);
            if ($cap === Codes::DUPLICATE_CAPTURE_SAME_KEY) {
                $this->world->phaseNoGo = true;
                return ['code' => $cap, 'blocked' => true, 'status' => 'captured'];
            }
            $this->world->fiscal->enqueue($key, 'inv-'.$key);
            return ['code' => Codes::PAY_OK, 'blocked' => false, 'status' => 'captured'];
        }

        return ['code' => Codes::PAY_TIMEOUT, 'blocked' => false, 'status' => Codes::PAY_UNKNOWN];
    }

    /** @return array{code:string, blocked:bool} */
    public function refund(string $key, OriginTrust $origin): array
    {
        if (!$origin->mayInstruct()) {
            return ['code' => Codes::ORIGIN_BLOCKED, 'blocked' => true];
        }
        $slot = $this->world->ledger->slot($key);
        if ($slot !== null) {
            $this->world->slots->release($slot, $key);
        }
        return ['code' => 'REFUND_OK', 'blocked' => false];
    }
}
