<?php

namespace Hejunjie\IdGenerator\Strategies;

use Hejunjie\IdGenerator\Contracts\Generator;
use Random\RandomException;
use InvalidArgumentException;

/**
 * UUID 生成器
 * @package Hejunjie\IdGenerator\Strategies
 */
class UUIDGenerator implements Generator
{
    private string $version;

    /**
     * 构造方法
     * 
     * @param string $version UUID版本: 'v1' | 'v4'
     * 
     * @return void 
     */
    public function __construct(string $version = 'v4')
    {
        $this->version = $version;
    }

    /**
     * 生成ID
     * 
     * @return string 
     * @throws InvalidArgumentException 
     */
    public function generate(): string
    {
        return match ($this->version) {
            'v1' => $this->uuidV1(),
            'v4' => $this->uuidV4(),
            default => throw new \InvalidArgumentException("Unsupported UUID version: {$this->version}")
        };
    }

    /**
     * 解析ID
     * 
     * @param string $uuid ID
     * 
     * @return array 
     */
    public function parse(string $uuid): array
    {
        $parts = explode('-', $uuid);
        return [
            'uuid' => $uuid,
            'version' => $parts[2][0] ?? null,
        ];
    }

    /**
     * 生成UUID v4
     * 
     * @return string 
     */
    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * 生成UUID v1
     * 
     * @return string 
     */
    private function uuidV1(): string
    {
        $time = microtime(true) * 10000000 + 0x01B21DD213814000;
        $timeHex = str_pad(dechex((int)$time), 12, '0', STR_PAD_LEFT);
        $clockSeq = dechex(random_int(0, 0xffff));
        $node = bin2hex(random_bytes(6));
        return sprintf('%s-%s-%s-%s-%s', substr($timeHex, 0, 8), substr($timeHex, 8, 4), substr($timeHex, 12, 4), $clockSeq, $node);
    }
}
