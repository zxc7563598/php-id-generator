<?php

namespace Hejunjie\IdGenerator\Helpers;

/**
 * 获取机器码的辅助类
 */
class MachineId
{
    /**
     * 获取机器 ID
     * 
     * @return int
     */
    public static function get(): int
    {
        // 1. 优先环境变量（推荐）
        $env = getenv('MACHINE_ID');
        if ($env !== false) {
            $id = (int)$env;
            if ($id < 0 || $id > 1023) {
                throw new \InvalidArgumentException("MACHINE_ID must be between 0 and 1023, got {$id}");
            }
            return $id;
        }
        // 2. 尝试从 MAC 地址生成
        $mac = self::getMacAddress();
        if ($mac) {
            return crc32($mac) & 0x3FF;
        }
        // 3. 最后退化到 IP
        $ip = gethostbyname(gethostname());
        return crc32($ip) & 0x3FF;
    }

    /**
     * 获取 MAC 地址
     * 
     * @return null|string
     */
    private static function getMacAddress(): ?string
    {
        $os = strtolower(PHP_OS);
        if (str_starts_with($os, 'win')) {
            @exec("getmac", $output);
            if (!empty($output[0])) {
                return $output[0];
            }
        } else {
            @exec("cat /sys/class/net/eth0/address", $output);
            if (!empty($output[0])) {
                return trim($output[0]);
            }
        }
        return null;
    }
}
