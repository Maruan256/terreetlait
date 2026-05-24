<?php

declare(strict_types=1);

namespace KlarnaPayment\Installer\Modules;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Installer\InstallerInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

readonly class ConfigInstaller implements InstallerInterface
{
    private const DEFAULT_VALUES = [
        'kpSendExtraMerchantData' => true,
        'enableCorporateCustomerIntegration' => true,
        'externalPaymentMethods' => [],
        'externalCheckouts' => [],
        'automaticRefund' => 'deactivated',
        'automaticCapture' => 'deactivated',
        'testMode' => true,
        'kpDisplayFooterBadge' => true,
        'kpUseAuthorizationCallback' => false,
    ];


    public function __construct(private SystemConfigService $systemConfigService)
    {
    }

    public function install(InstallContext $context): void
    {
        $this->setDefaultValues();
    }

    public function update(UpdateContext $context): void
    {
        $this->setDefaultValues();
    }

    public function uninstall(UninstallContext $context): void
    {
        // Nothing to do here
    }

    public function activate(ActivateContext $context): void
    {
        // Nothing to do here
    }

    public function deactivate(DeactivateContext $context): void
    {
        // Nothing to do here
    }

    private function setDefaultValues(): void
    {
        foreach (self::DEFAULT_VALUES as $key => $value) {
            $configKey = ConfigReaderInterface::SYSTEM_CONFIG_DOMAIN . $key;

            $currentValue = $this->systemConfigService->get($configKey);

            if ($currentValue !== null) {
                continue;
            }

            $this->systemConfigService->set($configKey, $value);
        }
    }
}
