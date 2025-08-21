<?php

namespace Hejunjie\IdGenerator;

use InvalidArgumentException;
use Hejunjie\IdGenerator\Contracts\Generator;
use Hejunjie\IdGenerator\Strategies\SnowflakeGenerator;
use Hejunjie\IdGenerator\Strategies\TimestampGenerator;
use Hejunjie\IdGenerator\Strategies\ReadableGenerator;
use Hejunjie\IdGenerator\Strategies\UUIDGenerator;

/**
 * 工厂入口类，统一对外暴露 API
 */
class IdGenerator
{
    /**
     * 创建 ID 生成器
     *
     * @param string $strategy 策略名称：snowflake, timestamp, readable
     * @param array $config 配置数组
     *      - snowflake: ['machineId' => int]
     *      - timestamp: ['prefix' => string]
     *      - readable: ['prefix' => string, 'randomLength' => int]
     *      - uuid: ['version' => string]
     *
     * @return Generator
     */
    public static function make(string $strategy, array $config = []): Generator
    {
        return match ($strategy) {
            'snowflake' => new SnowflakeGenerator(
                $config['machineId'] ?? null
            ),
            'timestamp' => new TimestampGenerator(
                $config['prefix'] ?? ''
            ),
            'readable'  => new ReadableGenerator(
                $config['prefix'] ?? 'ID',
                $config['randomLength'] ?? 8
            ),
            'uuid'  => new UUIDGenerator(
                $config['version'] ?? 'v4'
            ),
            default     => throw new InvalidArgumentException("Unsupported strategy: {$strategy}")
        };
    }
}
