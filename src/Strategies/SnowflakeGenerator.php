<?php

namespace Hejunjie\IdGenerator\Strategies;

use Hejunjie\IdGenerator\Contracts\Generator;
use Hejunjie\IdGenerator\Helpers\MachineId;

/**
 * 雪花 ID 生成器
 * @package Hejunjie\IdGenerator\Strategies
 */
class SnowflakeGenerator implements Generator
{
    private int $machineId;
    private bool $useFileLock;

    public function __construct(?int $machineId = null, bool $useFileLock = false)
    {
        $this->machineId = $machineId ?? random_int(0, 1023); // 10位机器码
        $this->useFileLock = $useFileLock;
    }

    public function generate(): string
    {
        if ($this->useFileLock) {
            return $this->generateWithFileLock();
        }

        return $this->generateDefault();
    }

    private function generateDefault(): string
    {
        $timeMs = (int)(microtime(true) * 1000);
        $timeUs = (int)((microtime(true) * 1000) % 1000);
        $pid    = getmypid() % 1024;
        $rand   = random_int(0, 4095);

        return sprintf(
            '%d%03d%03d%03d%03d',
            $timeMs,
            $timeUs,
            $this->machineId,
            $pid,
            $rand
        );
    }

    private function generateWithFileLock(): string
    {
        $lockFile = sys_get_temp_dir() . '/snowflake_sequence.lock';
        $seqFile  = sys_get_temp_dir() . '/snowflake_sequence.txt';
        $fp = fopen($lockFile, 'c+');
        flock($fp, LOCK_EX);

        $time = (int)(microtime(true) * 1000);
        $sequence = 0;

        if (file_exists($seqFile)) {
            $data = json_decode(file_get_contents($seqFile), true);
            if ($data['time'] === $time) {
                $sequence = $data['sequence'] + 1;
            }
        }

        file_put_contents($seqFile, json_encode(['time' => $time, 'sequence' => $sequence]));
        flock($fp, LOCK_UN);
        fclose($fp);

        return sprintf('%d%03d%03d', $time, $this->machineId, $sequence);
    }

    public function parse(string $id): array
    {
        // 简单解析：提取时间、机器码、序列
        $timeMs = (int)substr($id, 0, 13);
        $machineId = (int)substr($id, 13, 3);
        $sequence = (int)substr($id, 16);

        return [
            'timestamp' => $timeMs,
            'datetime' => date('Y-m-d H:i:s', $timeMs / 1000),
            'machine_id' => $machineId,
            'sequence' => $sequence,
        ];
    }
}
