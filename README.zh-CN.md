# 🆔 hejunjie/php-id-generator

<div align="center">
  <a href="./README.md">English</a>｜<a href="./README.zh-CN.md">简体中文</a>
  <hr width="50%"/>
</div>

轻量级 PHP ID 生成器，提供雪花算法、UUID、时间戳和自定义可读 ID 等多种策略，确保全局唯一性与高并发性能，可轻松集成到任何 PHP 项目，适用于订单号、资源标识、日志追踪等多种业务场景

**本项目已经经由 Zread 解析完成，如果需要快速了解项目，可以点击此处进行查看：[了解本项目](https://zread.ai/zxc7563598/php-id-generator)**

---

## ✨ 特点

- **生成全局唯一 ID**：适合订单号、数据库主键、日志追踪、资源标识等场景
- **多策略支持**：支持雪花算法、时间戳、UUID、自定义可读 ID，满足不同业务需求
- **高性能 & 易集成**：轻量级、无复杂依赖，适合快速集成到任何 PHP 项目
- **可解析 & 可控**：生成的 ID 可解析出时间、机器或序列信息，便于业务分析

---

## 📦 安装方式

使用 Composer 安装本库：

```bash
composer require hejunjie/id-generator
```

---

## 🚀 使用方式

```php
use Hejunjie\IdGenerator\Contracts\Generator;
use Hejunjie\IdGenerator\IdGenerator;

// ----------------------------
// 1. Snowflake 生成与解析
// 返回：雪花ID（1bit符号 + 41bit时间戳 + 10bit机器ID + 12bit序列号）
// ----------------------------
echo "=== Snowflake 生成与解析 ===\n";
// 参数可选择传递，useFileLock 和 redisConfig
// 不使用锁：使用随机序列模拟雪花ID，在每毫秒内生成 > 75 条时容易出现重复
// 使用文件锁：传递 useFileLock => true，不会出现重复问题，但性能会有所下降
// 使用 Redis 锁（推荐）：传递 redisConfig，不会出现重复问题，需要支持 Redis 的环境
$snowflake = IdGenerator::make('snowflake', ['redisConfig' => ['host' => '127.0.0.1', 'port' => 6379, 'auth' => null]]);
$snowflakeId = $snowflake->generate();
echo "生成 ID: $snowflakeId\n";
// 解析
$parsedSnowflake = $snowflake->parse($snowflakeId);
echo "解析 ID:\n";
print_r($parsedSnowflake);
echo "\n\n";

// ----------------------------
// 2. Timestamp 生成与解析
// 返回：毫秒时间戳 + 序列
// ----------------------------
echo "=== Timestamp 生成与解析 ===\n";
// 参数可选择传递，prefix、useFileLock 和 redisConfig
// prefix 为 ID 前缀，不传则不添加前缀
// 不使用锁：使用进程ID+随机数，每个进程在每毫秒内生成 > 37 条时容易出现重复
// 使用文件锁：传递 useFileLock => true，不会出现重复问题，但性能会有所下降
// 使用 Redis 锁（推荐）：传递 redisConfig，不会出现重复问题，需要支持 Redis 的环境
$timestamp = IdGenerator::make('timestamp', ['prefix' => 'ORD']);
$timestampId = $timestamp->generate();
echo "生成 ID: $timestampId\n";
// 解析
$parsedTimestamp = $timestamp->parse($timestampId, 'ORD');
echo "解析 ID:\n";
print_r($parsedTimestamp);
echo "\n\n";

// ----------------------------
// 3. Readable 生成与解析
// 返回：前缀-年-月-日-随机数
// ----------------------------
echo "=== Readable 生成与解析 ===\n";
// 参数可选择传递，prefix 和 randomLength
// prefix 为 ID 前缀，不传则默认为 ID
// randomLength 为随机数长度，不传则默认 8 位
$readable = IdGenerator::make('readable', ['prefix' => 'ORD', 'randomLength' => 6]);
$readableId = $readable->generate();
echo "生成 ID: $readableId\n";
// 解析
$parsedReadable = $readable->parse($readableId);
echo "解析 ID:\n";
print_r($parsedReadable);

echo "\n\n";

// ----------------------------
// 4. UUID 生成与解析
// 返回：UUID（32 位字符串）
// ----------------------------
echo "=== UUID 生成与解析 ===\n";
// 参数可选择传递，version
// version 为 UUID 版本，支持 v1 和 v4，不传则默认为 v4
$uuid = IdGenerator::make('uuid', ['version' => 'v4']);
$uuidId = $uuid->generate();
echo "生成 ID: $uuidId\n";
// 解析
$parsedUUID = $uuid->parse($uuidId);
echo "解析 ID:\n";
print_r($parsedUUID);
echo "\n\n";

// ----------------------------
// 5. 自定义策略
// 返回：自定义前缀-随机数
// ----------------------------
echo "=== Custom 生成与解析 ===\n";
// 自定义生成器实现 Generator 接口
// 传递参数自定义，在构造方法中实现
class MyCustomGenerator implements Generator
{
    public function __construct(private string $prefix = 'MY') {}

    public function generate(): string
    {
        return $this->prefix . '-' . random_int(1000, 9999);
    }
    public function parse(string $id): array
    {
        return ['id' => $id];
    }
}
// 注册自定义策略
IdGenerator::registerStrategy('custom', function (array $config) {
    return new MyCustomGenerator($config['prefix'] ?? 'MY');
});
// 使用自定义策略
$custom = IdGenerator::make('custom', ['prefix' => 'ORD']);
$customId = $custom->generate();
echo "生成 ID: $customId\n";
// 解析
$parsedCustom = $custom->parse($customId);
echo "解析 ID:\n";
print_r($parsedCustom);
echo "\n\n";

```

## 🎯 用途 & 初衷

在做项目的时候，我经常遇到需要生成订单号、资源 ID 等全局唯一标识的情况。一个项目一个项目地写生成方法虽然可行，但项目多了之后，规范难以统一，而且不同项目的需求又各不相同，有些甚至涉及分布式系统。

因此，我做了这个仓库，目标是：

- **轻量级、高性能** 的 PHP ID 生成器
- **多策略支持**：可以根据不同业务需求选择雪花算法、时间戳、UUID 或自定义可读 ID
- **易用且可解析**：生成的 ID 既能保证全局唯一，又可以追踪来源与时间
- **尽可能降低依赖**：让它可以快速集成到任何 PHP 项目，减少重复造轮子的时间

希望它能帮到自己，也帮到其他开发者，让生成唯一 ID 这件事变得更简单、更可靠。

---

## 🤝 欢迎 PR & 贡献

这个项目始于个人开发需求，但我希望它能成为一个 **社区友好、可持续发展的工具**。如果你有好的想法、改进建议或者发现了 Bug，**欢迎提交 Issue 或 Pull Request**

无论是：

* 新增策略或算法
* 提升性能或优化实现
* 增强可用性或文档完善

都非常欢迎参与贡献。你的每一次 PR 都能让这个库变得更好，也帮助更多像我一样的开发者节省时间。