<?php

namespace App\Contracts;

interface CardProviderCapabilitiesInterface
{
    /**
     * 检查Provider是否支持某个功能
     */
    public function supports(string $capability): bool;

    /**
     * 获取Provider支持的所有功能列表
     */
    public function getSupportedCapabilities(): array;

    /**
     * 获取Provider的扩展功能列表
     */
    public function getExtendedCapabilities(): array;
}