<?php

namespace Hejunjie\IdGenerator\Strategies;

use Hejunjie\IdGenerator\Contracts\Generator;

class UUIDGenerator implements Generator
{
    private string $version;

    /**
     * @param string $version 'v1' | 'v4'
     */
    public function __construct(string $version = 'v4')
    {
        $this->version = $version;
    }

    public function generate(): string
    {
        return match ($this->version) {
            'v1' => $this->uuidV1(),
            'v4' => $this->uuidV4(),
            default => throw new \InvalidArgumentException("Unsupported UUID version: {$this->version}")
        };
    }

    public function parse(string $uuid): array
    {
        $parts = explode('-', $uuid);
        return [
            'uuid' => $uuid,
            'version' => $parts[2][0] ?? null, // UUID version 字符通常在第3段首字符
        ];
    }

    private function uuidV4(): string
    {
        // 随机生成 v4 UUID
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function uuidV1(): string
    {
        // 简单实现时间 + 随机 60位 UUID
        $time = microtime(true) * 10000000 + 0x01B21DD213814000; // UUID epoch
        $timeHex = str_pad(dechex((int)$time), 12, '0', STR_PAD_LEFT);
        $clockSeq = dechex(random_int(0, 0xffff));
        $node = bin2hex(random_bytes(6));
        return sprintf('%s-%s-%s-%s-%s', substr($timeHex, 0, 8), substr($timeHex, 8, 4), substr($timeHex, 12, 4), $clockSeq, $node);
    }
}
