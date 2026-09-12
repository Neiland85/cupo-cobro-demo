<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Evidence;

final class Chain
{
    public const GENESIS = 'sha256:00000000';

    /** @var list<array{seq:int, prev:string, hash:string, scenario:string, record:Record}> */
    private array $entries = [];

    public function append(Record $record, string $scenario): array
    {
        $prev = $this->entries === [] ? self::GENESIS : $this->entries[array_key_last($this->entries)]['hash'];
        $payload = $record->hash.'|'.$scenario.'|'.$record->result;
        $entry = [
            'seq' => count($this->entries) + 1,
            'prev' => $prev,
            'hash' => Hash::link($prev, $payload),
            'scenario' => $scenario,
            'record' => $record,
        ];
        $this->entries[] = $entry;

        return $entry;
    }

    public function verify(): bool
    {
        $prev = self::GENESIS;
        foreach ($this->entries as $entry) {
            if ($entry['prev'] !== $prev) {
                return false;
            }
            $payload = $entry['record']->hash.'|'.$entry['scenario'].'|'.$entry['record']->result;
            if ($entry['hash'] !== Hash::link($prev, $payload)) {
                return false;
            }
            $prev = $entry['hash'];
        }

        return true;
    }

    /** @return list<array{seq:int, prev:string, hash:string, scenario:string, record:Record}> */
    public function all(): array
    {
        return $this->entries;
    }
}
