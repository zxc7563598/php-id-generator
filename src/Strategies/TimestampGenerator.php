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

    /**
     * 构造方法
     * 
     * @param string $prefix 前缀
     * @param bool $useFileLock 是否使用文件锁
     * 
     * @return void 
     */
    public function __construct(string $prefix = '', bool $useFileLock = false)
    {
        $this->prefix = $prefix;
        $this->useFileLock = $useFileLock;
    }

    /**
     * 生成ID
     * 
     * @return string 
     */
    public function generate(): string
    {
        // 使用 DateTimeImmutable 获取毫秒时间戳
        $timeMs = (int)(new \DateTimeImmutable())->format('Uv');

        if ($this->useFileLock) {
            $sequence = $this->getSequenceWithFileLock($timeMs);
        } else {
            $pid = getmypid() % 1000;
            $rand = random_int(0, 999);
            $sequence = sprintf('%03d%03d', $pid, $rand);
        }

        return sprintf('%s%d%s', $this->prefix, $timeMs, $sequence);
    }

    /**
     * 生成带文件锁的ID
     * 
     * @return string
     */
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

    /**
     * 解析ID
     * 
     * @param string $id ID
     * @return array 
     */
    public function parse(string $id, string $prefix = ''): array
    {
        $idWithoutPrefix = $prefix ? substr($id, strlen($prefix)) : $id;
        $timeMs = (int)substr($idWithoutPrefix, 0, 13);
        $sequence = substr($idWithoutPrefix, 13);

        return [
            'prefix' => $prefix,
            'datetime' => date('Y-m-d H:i:s', (int)($timeMs / 1000)),
            'timestamp' => $timeMs,
            'sequence' => $sequence,
        ];
    }
}
