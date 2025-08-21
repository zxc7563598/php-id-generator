<?php

namespace Hejunjie\IdGenerator\Strategies;

use Hejunjie\IdGenerator\Contracts\Generator;
use Hejunjie\IdGenerator\Helpers\MachineId;

/**
 * 雪花 ID 生成器（严格 64bit 实现）
 */
class SnowflakeGenerator implements Generator
{
    private bool $useFileLock;
    private ?\Redis $redis = null;
    private int $machineId;
    private int $epoch = 1700000000000;

    /**
     * 构造函数
     *
     * @param bool $useFileLock 是否使用文件锁
     * @param array $redisConfig Redis配置
     *
     * @return void
     */
    public function __construct(bool $useFileLock = false, array $redisConfig = [])
    {
        $this->machineId = MachineId::get() & 0x3FF; // 10 bit
        $this->useFileLock = $useFileLock;
        if ($redisConfig) {
            $this->redis = new \Redis();
            $this->redis->connect($redisConfig['host'], $redisConfig['port']);
            if (!empty($redisConfig['auth'])) {
                $this->redis->auth($redisConfig['auth']);
            }
        }
    }

    /**
     * 生成ID
     * 
     * @return string 
     */
    public function generate(): string
    {
        if ($this->redis) {
            return $this->generateWithRedis();
        }
        if ($this->useFileLock) {
            return $this->generateWithFileLock();
        }
        return $this->generateDefault();
    }

    /**
     * 默认ID（无锁无Redis，使用随机序列）
     * 
     * @return string 
     */
    private function generateDefault(): string
    {
        $timeMs = (int)(microtime(true) * 1000);
        $sequence = random_int(0, 0xFFF);
        return $this->packId($timeMs, $this->machineId, $sequence);
    }

    /**
     * 文件锁实现（单机安全）
     * 
     * @return string 
     */
    private function generateWithFileLock(): string
    {
        $lockFile = sys_get_temp_dir() . '/snowflake_sequence.lock';
        $seqFile  = sys_get_temp_dir() . '/snowflake_sequence.txt';
        $fp = fopen($lockFile, 'c+');
        flock($fp, LOCK_EX);
        $timeMs = (int)(microtime(true) * 1000);
        $sequence = 0;
        if (file_exists($seqFile)) {
            $data = json_decode(file_get_contents($seqFile), true);
            if ($data['time'] === $timeMs) {
                $sequence = ($data['sequence'] + 1) & 0xFFF; // 12 bit
            }
        }
        file_put_contents($seqFile, json_encode(['time' => $timeMs, 'sequence' => $sequence]));
        flock($fp, LOCK_UN);
        fclose($fp);
        return $this->packId($timeMs, $this->machineId, $sequence);
    }

    /**
     * Redis实现（分布式安全）
     * 
     * @return string 
     */
    private function generateWithRedis(): string
    {
        $timeMs = (int)(microtime(true) * 1000);
        $key = "snowflake:sequence:{$timeMs}";
        $sequence = $this->redis->incr($key) & 0xFFF; // 12 bit
        if ($sequence === 1) {
            $this->redis->expire($key, 1);
        }
        return $this->packId($timeMs, $this->machineId, $sequence);
    }

    /**
     * 打包成雪花ID (64位)
     *
     * @param int $timeMs 时间戳（毫秒）
     * @param int $machineId 机器ID
     * @param int $sequence 序列号
     * @return string
     */
    private function packId(int $timeMs, int $machineId, int $sequence): string
    {
        $timestampPart = ($timeMs - $this->epoch) << 22;
        $machinePart   = ($machineId & 0x3FF) << 12;
        $seqPart       = $sequence & 0xFFF;
        $id = $timestampPart | $machinePart | $seqPart;
        return (string)$id;
    }

    /**
     * 解析ID
     *
     * @param string $id 雪花ID
     * 
     * @return array
     */
    public function parse(string $id): array
    {
        $id = (int)$id;
        $sequence   = $id & 0xFFF;
        $machineId  = ($id >> 12) & 0x3FF;
        $timestamp  = ($id >> 22) + $this->epoch;
        return [
            'timestamp'  => $timestamp,
            'datetime'   => date('Y-m-d H:i:s', (int)($timestamp / 1000)),
            'machine_id' => $machineId,
            'sequence'   => $sequence,
        ];
    }
}
