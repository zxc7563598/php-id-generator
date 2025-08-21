<?php

namespace Hejunjie\IdGenerator\Strategies;

use Hejunjie\IdGenerator\Contracts\Generator;

class TimestampGenerator implements Generator
{
    private string $prefix;
    private int $sequence = 0;
    private int $lastMillis = -1;

    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;
    }

    public function generate(): string
    {
        $timestamp = $this->currentTimeMillis();

        // 避免同一毫秒冲突 → 使用自增序列
        if ($timestamp === $this->lastMillis) {
            $this->sequence++;
        } else {
            $this->sequence = 0;
            $this->lastMillis = $timestamp;
        }

        // 格式化时间戳：年月日时分秒 + 毫秒
        $datetime = date('YmdHis', (int)($timestamp / 1000));
        $millis   = str_pad((string)($timestamp % 1000), 3, '0', STR_PAD_LEFT);

        // 拼接：前缀 + 时间戳 + 毫秒 + 序列
        return $this->prefix . $datetime . $millis . str_pad((string)$this->sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * 解析 Timestamp ID
     */
    public function parse(string $id): array
    {
        if ($this->prefix && str_starts_with($id, $this->prefix)) {
            $id = substr($id, strlen($this->prefix));
        }

        // 拆分: [0-13] -> YmdHis, [14-16] -> 毫秒, [17-19] -> 序列
        $datetimeStr = substr($id, 0, 14); // YYYYMMDDHHMMSS
        $millis      = substr($id, 14, 3);
        $sequence    = substr($id, 17, 3);

        // 转换成时间戳（秒） + 毫秒
        $timestamp = strtotime(substr($datetimeStr, 0, 8) . " " . substr($datetimeStr, 8, 2) . ":" . substr($datetimeStr, 10, 2) . ":" . substr($datetimeStr, 12, 2)) * 1000 + (int)$millis;

        return [
            'prefix'    => $this->prefix,
            'datetime'  => \DateTime::createFromFormat('YmdHis', $datetimeStr)->format('Y-m-d H:i:s') . '.' . $millis,
            'timestamp' => $timestamp,
            'sequence'  => (int)$sequence,
        ];
    }

    private function currentTimeMillis(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
