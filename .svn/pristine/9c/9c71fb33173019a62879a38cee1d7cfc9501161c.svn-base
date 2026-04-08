<?php

namespace App\Services;

use App\Contracts\CardProviderInterface;
use App\Models\Card;
use App\Models\CardProvider;
use App\Services\CardProviders\AdposProvider;
use App\Services\CardProviders\AirwallexProvider;
use Exception;
use Illuminate\Support\Facades\Log;

class CardProviderService
{
    private array $providers = [];
    private array $providerMapping = [
        'aw' => AirwallexProvider::class,
        'ap' =>AdposProvider::class,
        // 这里可以添加更多的provider映射
    ];

    /**
     * 根据provider名称获取provider实例
     */
    public function getProvider(string $providerName): CardProviderInterface
    {
        if (!isset($this->providers[$providerName])) {
            $this->providers[$providerName] = $this->createProvider($providerName);
        }

        return $this->providers[$providerName];
    }

    /**
     * 根据CardProvider模型获取provider实例
     */
    public function getProviderByModel(CardProvider $cardProvider): CardProviderInterface
    {
        $cacheKey = 'model_' . $cardProvider->id;

        if (!isset($this->providers[$cacheKey])) {
            $this->providers[$cacheKey] = $this->createProviderFromModel($cardProvider);
        }

        return $this->providers[$cacheKey];
    }

    /**
     * 根据Card模型获取provider实例
     */
    public function getProviderByCard(Card $card): CardProviderInterface
    {
        if (!$card->cardProvider) {
            throw new Exception("Card {$card->id} has no associated provider");
        }

        return $this->getProviderByModel($card->cardProvider);
    }

    /**
     * 根据Card ID获取provider实例
     */
    public function getProviderByCardId(string $cardId): CardProviderInterface
    {
        $card = Card::with('cardProvider')->findOrFail($cardId);
        return $this->getProviderByCard($card);
    }

    /**
     * 创建provider实例
     */
    private function createProvider(string $providerName): CardProviderInterface
    {
        if (!isset($this->providerMapping[$providerName])) {
            throw new Exception("Unknown provider: {$providerName}");
        }

        $providerClass = $this->providerMapping[$providerName];
        return new $providerClass();
    }

    /**
     * 从CardProvider模型创建provider实例
     */
    private function createProviderFromModel(CardProvider $cardProvider): CardProviderInterface
    {
        $providerName = $cardProvider->name;

        if (!isset($this->providerMapping[$providerName])) {
            throw new Exception("Unknown provider: {$providerName}");
        }

        $providerClass = $this->providerMapping[$providerName];
        $provider = new $providerClass();

        // 设置provider配置
        // 即使config为空数组也要设置，确保provider有正确的config结构
        $provider->setConfig($cardProvider->config ?? []);

        return $provider;
    }

    /**
     * 注册新的provider
     */
    public function registerProvider(string $name, string $className): void
    {
        if (!class_exists($className)) {
            throw new Exception("Provider class {$className} does not exist");
        }

        if (!in_array(CardProviderInterface::class, class_implements($className))) {
            throw new Exception("Provider class {$className} must implement CardProviderInterface");
        }

        $this->providerMapping[$name] = $className;
    }

    /**
     * 获取所有可用的provider类型
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providerMapping);
    }

    /**
     * 检查provider是否存在
     */
    public function hasProvider(string $providerName): bool
    {
        return isset($this->providerMapping[$providerName]);
    }

    /**
     * 清理provider实例缓存
     */
    public function clearProviderCache(): void
    {
        $this->providers = [];
    }

    /**
     * 检查Provider是否支持某个功能
     */
    public function providerSupports(string $providerName, string $capability): bool
    {
        $provider = $this->getProvider($providerName);

        if ($provider instanceof \App\Contracts\CardProviderCapabilitiesInterface) {
            return $provider->supports($capability);
        }

        return false;
    }

    /**
     * 根据CardProvider模型检查是否支持某个功能
     */
    public function providerModelSupports(CardProvider $cardProvider, string $capability): bool
    {
        $provider = $this->getProviderByModel($cardProvider);

        if ($provider instanceof \App\Contracts\CardProviderCapabilitiesInterface) {
            return $provider->supports($capability);
        }

        return false;
    }

    /**
     * 获取Provider支持的功能列表
     */
    public function getProviderCapabilities(string $providerName): array
    {
        $provider = $this->getProvider($providerName);

        if ($provider instanceof \App\Contracts\CardProviderCapabilitiesInterface) {
            return $provider->getSupportedCapabilities();
        }

        return [];
    }

    /**
     * 获取Provider的扩展功能列表
     */
    public function getProviderExtendedCapabilities(string $providerName): array
    {
        $provider = $this->getProvider($providerName);

        if ($provider instanceof \App\Contracts\CardProviderCapabilitiesInterface) {
            return $provider->getExtendedCapabilities();
        }

        return [];
    }

    /**
     * 安全调用Provider方法（检查功能支持）
     */
    public function safeCall(string $providerName, string $method, array $arguments = [])
    {
        $provider = $this->getProvider($providerName);

        // 检查是否是扩展功能
        if ($provider instanceof \App\Contracts\CardProviderCapabilitiesInterface) {
            $extendedCapabilities = $provider->getExtendedCapabilities();

            if (isset($extendedCapabilities[$method])) {
                return $provider->$method(...$arguments);
            }
        }

        // 标准方法调用
        if (method_exists($provider, $method)) {
            return $provider->$method(...$arguments);
        }

        throw new Exception("Method {$method} not available for provider {$providerName}");
    }

    /**
     * 获取所有Provider的功能对比
     */
    public function getProvidersCapabilityMatrix(): array
    {
        $matrix = [];

        foreach ($this->providerMapping as $name => $class) {
            $provider = $this->getProvider($name);

            if ($provider instanceof \App\Contracts\CardProviderCapabilitiesInterface) {
                $matrix[$name] = [
                    'standard_capabilities' => $provider->getSupportedCapabilities(),
                    'extended_capabilities' => $provider->getExtendedCapabilities()
                ];
            }
        }

        return $matrix;
    }
}
