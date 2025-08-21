<?php

namespace Hejunjie\IdGenerator\Strategies;

use Hejunjie\IdGenerator\Contracts\Generator;
use Hejunjie\IdGenerator\Helpers\MachineId;

class SnowflakeGenerator implements Generator
{
    // 起始时间戳（毫秒），通常设置为一个固定时间点，避免 41 位时间溢出
    private const EPOCH = 1672531200000; // 2023-01-01 00:00:00

    private int $machineId;
    private int $lastTimestamp = -1;
    private int $sequence = 0;

    // 位移定义
    private const MACHINE_ID_BITS = 10;
    private const SEQUENCE_BITS   = 12;

    private const MAX_MACHINE_ID = (1 << self::MACHINE_ID_BITS) - 1; // 1023
    private const MAX_SEQUENCE   = (1 << self::SEQUENCE_BITS) - 1;   // 4095

    private const MACHINE_ID_SHIFT = self::SEQUENCE_BITS;
    private const TIMESTAMP_SHIFT  = self::SEQUENCE_BITS + self::MACHINE_ID_BITS;

    public function __construct(?int $machineId = null)
    {
        $this->machineId = $machineId ?? MachineId::get();

        if ($this->machineId > self::MAX_MACHINE_ID || $this->machineId < 0) {
            throw new \InvalidArgumentException("Machine ID must be between 0 and " . self::MAX_MACHINE_ID);
        }
    }

    public function generate(): string
    {
        $timestamp = $this->currentTimeMillis();

        if ($timestamp < $this->lastTimestamp) {
            throw new \RuntimeException("Clock moved backwards. Refusing to generate id.");
        }

        if ($timestamp === $this->lastTimestamp) {
            $this->sequence = ($this->sequence + 1) & self::MAX_SEQUENCE;
            if ($this->sequence === 0) {
                // 序列号用完，等到下一毫秒
                $timestamp = $this->waitNextMillis($this->lastTimestamp);
            }
        } else {
            $this->sequence = 0;
        }

        $this->lastTimestamp = $timestamp;

        // 拼接 ID
        $id = (($timestamp - self::EPOCH) << self::TIMESTAMP_SHIFT)
            | ($this->machineId << self::MACHINE_ID_SHIFT)
            | $this->sequence;

        return (string)$id;
    }

    public function parse(string $id): array
    {
        $id = (int)$id;

        $sequence = $id & self::MAX_SEQUENCE;
        $machineId = ($id >> self::MACHINE_ID_SHIFT) & self::MAX_MACHINE_ID;
        $timestamp = ($id >> self::TIMESTAMP_SHIFT) + self::EPOCH;

        return [
            'timestamp'  => $timestamp,
            'datetime'   => date('Y-m-d H:i:s.v', (int)($timestamp / 1000)),
            'machine_id' => $machineId,
            'sequence'   => $sequence,
        ];
    }

    private function currentTimeMillis(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function waitNextMillis(int $lastTimestamp): int
    {
        $timestamp = $this->currentTimeMillis();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->currentTimeMillis();
        }
        return $timestamp;
    }
}
