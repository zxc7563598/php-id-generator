<?php

namespace Hejunjie\IdGenerator\Contracts;

/**
 * 所有 ID 生成策略的统一接口
 */
interface Generator
{
    /**
     * 生成唯一 ID
     */
    public function generate(): string;

    public function parse(string $id): array;
}
