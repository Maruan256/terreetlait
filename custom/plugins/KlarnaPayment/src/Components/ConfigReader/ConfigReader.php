<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\ConfigReader;

use KlarnaPayment\Components\Struct\Configuration;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigReader implements ConfigReaderInterface
{
    /** @var SystemConfigService */
    private $systemConfigService;

    /** @var Configuration[] */
    private $configurations = [];

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function read(?string $salesChannelId = null, bool $inherit = true): Configuration
    {
        $cacheKey = ($salesChannelId ?? 'default') . $inherit;

        if (array_key_exists($cacheKey, $this->configurations)) {
            return $this->configurations[$cacheKey];
        }

        $values = $this->systemConfigService->getDomain(
            ConfigReaderInterface::SYSTEM_CONFIG_DOMAIN,
            $salesChannelId,
            $inherit
        );

        $config = [];

        foreach ($values as $key => $value) {
            $property = substr($key, strlen(ConfigReaderInterface::SYSTEM_CONFIG_DOMAIN));

            $config[$property] = $value;
        }

        $this->configurations[$cacheKey] = new Configuration($config);

        return $this->configurations[$cacheKey];
    }
}
