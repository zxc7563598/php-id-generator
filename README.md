# 🆔 hejunjie/php-id-generator

<div align="center">
  <a href="./README.md">English</a>｜<a href="./README.zh-CN.md">简体中文</a>
  <hr width="50%"/>
</div>

A lightweight PHP ID generator supporting Snowflake, UUID, timestamp, and custom readable ID strategies. Ensures global uniqueness and high-performance, easily integrable into any PHP project, suitable for order numbers, resource identifiers, log tracking, and various other business scenarios

**This project has been parsed by Zread. If you need a quick overview of the project, you can click here to view it：[Understand this project](https://zread.ai/zxc7563598/php-id-generator)**

---

## ✨ Features

- **Generate globally unique IDs**: Suitable for order numbers, database primary keys, log tracking, resource identifiers, and other scenarios
- **Multi-strategy support**: Supports Snowflake, timestamp, UUID, and custom readable IDs to meet different business needs
- **High performance & easy integration**: Lightweight with no complex dependencies, ideal for quick integration into any PHP project
- **Parseable & controllable:** Generated IDs can be parsed to extract time, machine, or sequence information, facilitating business analysis

---

## 📦 Installation

Install this library using Composer：

```bash
composer require hejunjie/id-generator
```

---

## 🚀 Usage

```php
use Hejunjie\IdGenerator\Contracts\Generator;
use Hejunjie\IdGenerator\IdGenerator;

// ----------------------------
// 1. Snowflake generation and parsing
// Returns: Snowflake ID (1-bit sign + 41-bit timestamp + 10-bit machine ID + 12-bit sequence)
// ----------------------------
echo "=== Snowflake generation and parsing ===\n";
// Optional parameters: useFileLock and redisConfig
// Without lock: uses a random sequence to simulate Snowflake ID; generating >75 IDs per millisecond may cause duplicates
// With file lock: pass useFileLock => true; no duplicates, but performance may decrease
// With Redis lock (recommended): pass redisConfig; no duplicates, requires a Redis environment
$snowflake = IdGenerator::make('snowflake', ['redisConfig' => ['host' => '127.0.0.1', 'port' => 6379, 'auth' => null]]);
$snowflakeId = $snowflake->generate();
echo "generation ID: $snowflakeId\n";
// parsing
$parsedSnowflake = $snowflake->parse($snowflakeId);
echo "parsing ID:\n";
print_r($parsedSnowflake);
echo "\n\n";

// ----------------------------
// 2. Timestamp generation and parsing
// Returns: millisecond timestamp + sequence
// ----------------------------
echo "=== Timestamp generation and parsing ===\n";
// Optional parameters: prefix, useFileLock, and redisConfig
// prefix: ID prefix, if not provided no prefix will be added
// Without lock: uses process ID + random number; generating >37 IDs per millisecond per process may cause duplicates
// With file lock: pass useFileLock => true; no duplicates, but performance may decrease
// With Redis lock (recommended): pass redisConfig; no duplicates, requires a Redis environment
$timestamp = IdGenerator::make('timestamp', ['prefix' => 'ORD']);
$timestampId = $timestamp->generate();
echo "generation ID: $timestampId\n";
// parsing
$parsedTimestamp = $timestamp->parse($timestampId, 'ORD');
echo "parsing ID:\n";
print_r($parsedTimestamp);
echo "\n\n";

// ----------------------------
// 3. Readable generation and parsing
// Returns: prefix-year-month-day-random number
// ----------------------------
echo "=== Readable generation and parsing ===\n";
// Optional parameters: prefix and randomLength
// prefix: ID prefix, defaults to "ID" if not provided
// randomLength: length of the random number, defaults to 8 if not provided
$readable = IdGenerator::make('readable', ['prefix' => 'ORD', 'randomLength' => 6]);
$readableId = $readable->generate();
echo "generation ID: $readableId\n";
// parsing
$parsedReadable = $readable->parse($readableId);
echo "parsing ID:\n";
print_r($parsedReadable);

echo "\n\n";

// ----------------------------
// 4. UUID generation and parsing
// Returns: UUID (32-character string)
// ----------------------------
echo "=== UUID generation and parsing ===\n";
// Optional parameter: version
// version: UUID version, supports v1 and v4; defaults to v4 if not provided
$uuid = IdGenerator::make('uuid', ['version' => 'v4']);
$uuidId = $uuid->generate();
echo "generation ID: $uuidId\n";
// parsing
$parsedUUID = $uuid->parse($uuidId);
echo "parsing ID:\n";
print_r($parsedUUID);
echo "\n\n";

// ----------------------------
// 5. Custom strategy
// Returns: custom-prefix-random-number
// ----------------------------
echo "=== Custom generation and parsing ===\n";
// Custom generator implements the Generator interface
// Pass parameters for customization, implemented in the constructor
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
// Register custom strategy
IdGenerator::registerStrategy('custom', function (array $config) {
    return new MyCustomGenerator($config['prefix'] ?? 'MY');
});
// Use custom strategy
$custom = IdGenerator::make('custom', ['prefix' => 'ORD']);
$customId = $custom->generate();
echo "generation ID: $customId\n";
// parsing
$parsedCustom = $custom->parse($customId);
echo "parsing ID:\n";
print_r($parsedCustom);
echo "\n\n";

```

## 🎯 Purpose & Motivation

When working on projects, I often encounter the need to generate order numbers, resource IDs, and other globally unique identifiers. Writing separate generation methods for each project works, but as the number of projects increases, maintaining consistency becomes difficult, and different projects have different requirements—some even involve distributed systems.

Therefore, I created this repository with the following goals:

- **Lightweight and high-performance** PHP ID generator
- **Multi-strategy support**: Choose between Snowflake, timestamp, UUID, or custom readable IDs according to different business needs
- **Easy to use and parseable**: Generated IDs ensure global uniqueness while allowing tracking of origin and time
- **Minimized dependencies**: Quickly integrate into any PHP project, reducing the need to reinvent the wheel

I hope this library can help both myself and other developers, making the generation of unique IDs simpler and more reliable.

---

## 🤝 Welcome PRs & Contributions

This project started from personal development needs, but I hope it can become a **community-friendly and sustainable tool.** If you have good ideas, improvement suggestions, or find any bugs, **feel free to submit an Issue or Pull Request.**

Whether it's:

- Adding new strategies or algorithms
- Improving performance or optimizing implementations
- Enhancing usability or documentation

All contributions are warmly welcome. Every PR helps make this library better and saves time for developers like me.