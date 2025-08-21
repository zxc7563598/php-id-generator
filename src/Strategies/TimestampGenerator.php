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
    private ?\Redis $redis = null;

    /**
     * 构造方法
     * 
     * @param string $prefix 前缀
     * @param bool $useFileLock 是否使用文件锁
     * @param array $redisConfig Redis配置
     * 
     * @return void 
     */
    public function __construct(string $prefix = '', bool $useFileLock = false, array $redisConfig = [])
    {
        $this->prefix = $prefix;
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
        if ($this->useFileLock) {
            return $this->generateWithFileLock();
        }
        if ($this->redis) {
            return $this->generateWithRedis();
        }
        return $this->generateDefault();
    }

    /**
     * 默认ID（无锁无Redis，使用随机序列 13 + 3 + 3）
     * 
     * @return string
     */
    private function generateDefault(): string
    {
        $timeMs = (int)(new \DateTimeImmutable())->format('Uv');
        $pid = getmypid() % 1000;
        $rand = random_int(0, 999);
        return sprintf('%s%d%03d%03d', $this->prefix, $timeMs, $pid, $rand);
    }

    /**
     * 文件锁实现（单机安全 13 + 3）
     * 
     * @return string
     */
    private function generateWithFileLock(): string
    {
        $timeMs = (int)(new \DateTimeImmutable())->format('Uv');
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
        return sprintf('%s%d%03d', $this->prefix, $timeMs, $sequence);
    }

    /**
     * Redis实现（分布式安全 13 + 3）
     * 
     * @return string
     */
    private function generateWithRedis(): string
    {
        $timeMs = (int)(new \DateTimeImmutable())->format('Uv');
        $key = "timestamp:sequence:{$timeMs}";
        $sequence = $this->redis->incr($key);
        if ($sequence === 1) {
            $this->redis->expire($key, 1);
        }
        return sprintf('%s%d%03d', $this->prefix, $timeMs, $sequence);
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
