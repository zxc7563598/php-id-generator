<?php

namespace Hejunjie\IdGenerator\Strategies;

use Hejunjie\IdGenerator\Contracts\Generator;
use InvalidArgumentException;

/**
 * 可读 ID 生成器
 * @package Hejunjie\IdGenerator\Strategies
 */
class ReadableGenerator implements Generator
{
    private string $prefix;
    private int $randomLength;

    /**
     * 构造方法
     * 
     * @param string $prefix ID前缀
     * @param int $randomLength ID后缀长度
     * @return void 
     */
    public function __construct(string $prefix = 'ID', int $randomLength = 8)
    {
        $this->prefix = strtoupper($prefix);
        $this->randomLength = $randomLength;
    }

    /**
     * 生成ID
     * 
     * @return string 
     */
    public function generate(): string
    {
        $date = date('Y-m-d');
        $random = $this->randomString($this->randomLength);
        return "{$this->prefix}-{$date}-{$random}";
    }

    /**
     * 解析ID
     * 
     * @param string $id ID
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function parse(string $id): array
    {
        $parts = explode('-', $id);
        if (count($parts) < 3) {
            throw new \InvalidArgumentException("Invalid Readable ID format: $id");
        }
        $prefix = $parts[0];
        $date   = $parts[1] . '-' . $parts[2] . '-' . $parts[3];
        $random = $parts[4] ?? '';
        return [
            'prefix' => $prefix,
            'date'   => $date,
            'random' => $random,
        ];
    }

    /**
     * 生成指定长度的随机字符串
     * 
     * @param int $length 长度
     * 
     * @return string 
     */
    private function randomString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $result;
    }
}
