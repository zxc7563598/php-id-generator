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
    private static array $customStrategies = [];

    /**
     * 注册自定义策略
     * 
     * @param string $name 自定义策略名称
     * @param callable $factory 自定义策略类
     * 
     * @return void 
     */
    public static function registerStrategy(string $name, callable $factory): void
    {
        self::$customStrategies[$name] = $factory;
    }

    /**
     * 创建 ID 生成器
     *
     * @param string $strategy 策略名称：snowflake, timestamp, readable，uuid，自定义策略
     * @param array $config 配置数组（内置策略，自定义策略自行定义）
     *      - snowflake: ['machineId' => int, 'useFileLock' => bool]
     *      - timestamp: ['prefix' => string, 'useFileLock' => bool]
     *      - readable: ['prefix' => string, 'randomLength' => int]
     *      - uuid: ['version' => string]
     *
     * @return Generator
     * @return InvalidArgumentException 如果策略不支持
     */
    public static function make(string $strategy, array $config = []): Generator
    {
        // 先检查用户自定义策略
        if (isset(self::$customStrategies[$strategy])) {
            return self::$customStrategies[$strategy]($config);
        }
        // 内置策略
        return match ($strategy) {
            'snowflake' => new SnowflakeGenerator(
                $config['machineId'] ?? null,
                $config['useFileLock'] ?? false
            ),
            'timestamp' => new TimestampGenerator(
                $config['prefix'] ?? '',
                $config['useFileLock'] ?? false
            ),
            'readable'  => new ReadableGenerator(
                $config['prefix'] ?? 'ID',
                $config['randomLength'] ?? 8
            ),
            'uuid'      => new UUIDGenerator(
                $config['version'] ?? 'v4'
            ),
            default     => throw new InvalidArgumentException("Unsupported strategy: {$strategy}")
        };
    }
}
