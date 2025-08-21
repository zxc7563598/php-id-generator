<?php

namespace Hejunjie\IdGenerator\Helpers;

/**
 * 获取机器码的辅助类
 */
class MachineId
{
    public static function get(): int
    {
        // 优先从环境变量获取
        $env = getenv('MACHINE_ID');
        if ($env !== false) {
            return (int) $env & 0x3FF; // 限制在 0~1023
        }

        // 其次从 IP 地址生成
        $ip = gethostbyname(gethostname());
        return crc32($ip) & 0x3FF;
    }
}
