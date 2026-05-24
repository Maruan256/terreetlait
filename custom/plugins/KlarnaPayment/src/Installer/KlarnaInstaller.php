<?php /** @noinspection SqlNoDataSourceInspection */

declare(strict_types=1);

namespace KlarnaPayment\Installer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use KlarnaPayment\Installer\Modules\ConfigInstaller;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use KlarnaPayment\Installer\Modules\Helper\LanguageProvider;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use KlarnaPayment\Installer\Modules\RuleInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

readonly class KlarnaInstaller implements InstallerInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function install(InstallContext $context): void
    {
        $this->getPaymentMethodInstaller()->install($context);
        $this->getCustomFieldInstaller()->install($context);
        $this->getConfigInstaller()->install($context);
        $this->getRuleInstaller()->install($context);
    }

    public function update(UpdateContext $context): void
    {
        $this->getPaymentMethodInstaller()->update($context);
        $this->getCustomFieldInstaller()->update($context);
        $this->getConfigInstaller()->update($context);
        $this->getRuleInstaller()->update($context);
    }

    /**
     * @throws Exception
     */
    public function uninstall(UninstallContext $context): void
    {
        $this->getPaymentMethodInstaller()->uninstall($context);
        $this->getCustomFieldInstaller()->uninstall($context);
        $this->getConfigInstaller()->uninstall($context);
        $this->getRuleInstaller()->uninstall($context);

        if (!$context->keepUserData()) {
            $this->dropTables();
        }
    }

    public function activate(ActivateContext $context): void
    {
        $this->getPaymentMethodInstaller()->activate($context);
        $this->getCustomFieldInstaller()->activate($context);
        $this->getConfigInstaller()->activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        $this->getPaymentMethodInstaller()->deactivate($context);
        $this->getCustomFieldInstaller()->deactivate($context);
        $this->getConfigInstaller()->deactivate($context);
    }

    /**
     * @throws Exception
     */
    private function dropTables(): void
    {
        $connection = $this->getConnection();
        $connection->executeQuery('DROP TABLE IF EXISTS klarna_payment_request_log');
        $connection->executeQuery('DROP TABLE IF EXISTS klarna_payment_button_key');
    }

    private function getPaymentMethodInstaller(): InstallerInterface
    {
        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');

        /** @var EntityRepository $languageRepository */
        $languageRepository = $this->container->get('language.repository');

        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);

        $languageProvider = new LanguageProvider($languageRepository);

        return new PaymentMethodInstaller(
            $paymentMethodRepository,
            $salesChannelRepository,
            $pluginIdProvider,
            $languageProvider
        );
    }

    private function getConfigInstaller(): InstallerInterface
    {
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->container->get(SystemConfigService::class);

        return new ConfigInstaller($systemConfigService);
    }

    private function getCustomFieldInstaller(): InstallerInterface
    {
        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        /** @var EntityRepository $customFieldSetRelationRepository */
        $customFieldSetRelationRepository = $this->container->get('custom_field_set_relation.repository');

        return new CustomFieldInstaller($customFieldSetRepository, $customFieldSetRelationRepository, $this->getConnection());
    }

    private function getRuleInstaller(): InstallerInterface
    {
        /** @var EntityRepository $ruleRepository */
        $ruleRepository = $this->container->get('rule.repository');

        return new RuleInstaller($ruleRepository);
    }

    private function getConnection(): Connection
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        if (!$connection instanceof Connection) {
            throw new \RuntimeException('Expected connection service to be initialized');
        }

        return $connection;
    }
}
