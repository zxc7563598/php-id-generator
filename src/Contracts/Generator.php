<?php

namespace Hejunjie\IdGenerator\Contracts;

/**
 * 所有 ID 生成策略的统一接口
 */
interface Generator
{
    /**
     * 生成ID
     * 
     * @return string 
     */
    public function generate(): string;

    /**
     * 解析ID
     * @param string $id 
     * 
     * @return array 
     */
    public function parse(string $id): array;
}
