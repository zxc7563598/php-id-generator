<?php

namespace Hejunjie\IdGenerator\Strategies;

use Hejunjie\IdGenerator\Contracts\Generator;

/**
 * 时间戳 ID 生成器
 * @package Hejunjie\IdGenerator\Strategies
 */
class TimestampGenerator implements Generator
{
    private string $prefix;
    private bool $useFileLock;

    public function __construct(string $prefix = '', bool $useFileLock = false)
    {
        $this->prefix = $prefix;
        $this->useFileLock = $useFileLock;
    }

    public function generate(): string
    {
        $time = microtime(true);
        $timeMs = (int)($time * 1000);

        if ($this->useFileLock) {
            $sequence = $this->getSequenceWithFileLock($timeMs);
        } else {
            $pid = getmypid() % 1000;
            $rand = random_int(0, 999);
            $sequence = sprintf('%03d%03d', $pid, $rand);
        }

        return sprintf('%s%d%s', $this->prefix, $timeMs, $sequence);
    }

    private function getSequenceWithFileLock(int $timeMs): int
    {
        $lockFile = sys_get_temp_dir() . '/timestamp_sequence.lock';
        $seqFile  = sys_get_temp_dir() . '/timestamp_sequence.txt';
        $fp = fopen($lockFile, 'c+');
        flock($fp, LOCK_EX);

        $sequence = 0;
        if (file_exists($seqFile)) {
            $data = json_decode(file_get_contents($seqFile), true);
            if ($data['time'] === $timeMs) {
                $sequence = $data['sequence'] + 1;
            }
        }

        file_put_contents($seqFile, json_encode(['time' => $timeMs, 'sequence' => $sequence]));
        flock($fp, LOCK_UN);
        fclose($fp);

        return $sequence;
    }

    public function parse(string $id, string $prefix = ''): array
    {
        $idWithoutPrefix = $prefix ? substr($id, strlen($prefix)) : $id;
        $timeMs = (int)substr($idWithoutPrefix, 0, 13);
        $sequence = substr($idWithoutPrefix, 13);

        return [
            'prefix' => $prefix,
            'datetime' => date('Y-m-d H:i:s', $timeMs / 1000),
            'timestamp' => $timeMs,
            'sequence' => $sequence,
        ];
    }
}