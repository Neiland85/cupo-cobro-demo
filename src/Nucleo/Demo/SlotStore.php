<?php

declare(strict_types=1);

namespace App\Nucleo\Demo;

final class SlotStore
{
    /** @var array<string, array{capacity:int, holds:array<string, array{key:string, expires:int}>}> */
    private array $slots = [];

    public function seed(string $slotId, int $capacity): void
    {
        $this->slots[$slotId] = ['capacity' => $capacity, 'holds' => []];
    }

    public function hold(string $slotId, string $key, int $now, int $ttl = 900): string
    {
        if (!isset($this->slots[$slotId])) {
            $this->seed($slotId, 1);
        }
        foreach ($this->slots[$slotId]['holds'] as $existing) {
            if ($existing['key'] === $key && $existing['expires'] > $now) {
                return Codes::HOLD_REPLAY;
            }
        }
        $this->expire($slotId, $now);
        if (count($this->slots[$slotId]['holds']) >= $this->slots[$slotId]['capacity']) {
            return Codes::HOLD_CONFLICT;
        }
        $this->slots[$slotId]['holds'][$key] = ['key' => $key, 'expires' => $now + $ttl];
        return Codes::HOLD_OK;
    }

    public function release(string $slotId, string $key): void
    {
        unset($this->slots[$slotId]['holds'][$key]);
    }

    public function isHeld(string $slotId, string $key, int $now): bool
    {
        $this->expire($slotId, $now);
        $h = $this->slots[$slotId]['holds'][$key] ?? null;
        return $h !== null && $h['expires'] > $now;
    }

    public function hasAnyHold(string $slotId, int $now): bool
    {
        $this->expire($slotId, $now);
        return isset($this->slots[$slotId]) && $this->slots[$slotId]['holds'] !== [];
    }

    public function expire(string $slotId, int $now): void
    {
        if (!isset($this->slots[$slotId])) {
            return;
        }
        foreach ($this->slots[$slotId]['holds'] as $k => $h) {
            if ($h['expires'] <= $now) {
                unset($this->slots[$slotId]['holds'][$k]);
            }
        }
    }
}
