<?php

declare(strict_types=1);

namespace KlarnaPayment\Installer\Modules;

use KlarnaPayment\Components\PaymentHandler\KlarnaExpressCheckoutPaymentHandler;
use KlarnaPayment\Components\PaymentHandler\KlarnaPaymentsPaymentHandler;
use KlarnaPayment\Installer\InstallerInterface;
use KlarnaPayment\Installer\Modules\Helper\LanguageProvider;
use KlarnaPayment\KlarnaPayment;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\InvoicePayment;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class PaymentMethodInstaller implements InstallerInterface
{
    public const KLARNA_PAYMENTS_PAY_NOW_CODE = 'pay_now';
    public const KLARNA_PAYMENTS_PAY_LATER_CODE = 'pay_later';
    public const KLARNA_PAYMENTS_PAY_OVER_TIME_CODE = 'pay_over_time';

    /** @deprecated Not used anymore */
    public const KLARNA_PAY_LATER = 'ede05b719b214143a4cb1c0216b852de';
    /** @deprecated Not used anymore */
    public const KLARNA_FINANCING = 'ad4ca642046b40248444eba38bb8f5e8';
    /** @deprecated Not used anymore */
    public const KLARNA_DIRECT_DEBIT = '9f4ac7bef3394487b0ab9298d12eb1bd';
    /** @deprecated Not used anymore */
    public const KLARNA_DIRECT_BANK_TRANSFER = 'a03b53a6e3d34836b150cc6eeaf6d97d';
    /** @deprecated Not used anymore */
    public const KLARNA_CREDIT_CARD = 'd245c39e8707e85f053e806abffcbb36';
    /** @deprecated Not used anymore */
    public const KLARNA_PAY_NOW = 'f1ef36538c594dc580b59e28206a1297';
    /** The main payment method. */
    public const KLARNA_PAY = '2eb76b63b549a0de4fae2d0677c09062';
    public const KLARNA_EXPRESS_CHECKOUT = 'a4a8ecbfccc34792b2487e6ed81a5c55';

    public const KLARNA_PAYMENTS_CODES = [
        self::KLARNA_PAYMENTS_PAY_NOW_CODE,
        self::KLARNA_PAYMENTS_PAY_LATER_CODE,
        self::KLARNA_PAYMENTS_PAY_OVER_TIME_CODE,
    ];

    public const KLARNA_DEPRECATED_IDS = [
        self::KLARNA_PAY_LATER,
        self::KLARNA_FINANCING,
        self::KLARNA_DIRECT_DEBIT,
        self::KLARNA_DIRECT_BANK_TRANSFER,
        self::KLARNA_CREDIT_CARD,
        self::KLARNA_PAY_NOW,
    ];

    public const KLARNA_EXPRESS_CHECKOUT_CODES = [
        self::KLARNA_EXPRESS_CHECKOUT => 'express-checkout',
    ];

    public const KLARNA_API_REGION_US = 'US';
    public const KLARNA_API_REGION_EU = 'EU';

    private const PAYMENT_METHODS = [
        self::KLARNA_PAY => [
            'id' => self::KLARNA_PAY,
            'handlerIdentifier' => KlarnaPaymentsPaymentHandler::class,
            'afterOrderEnabled' => true,
            'name' => 'Pay with Klarna',
            'translations' => [],
            'technicalName' => 'klarna-one-klarna'
        ],
        self::KLARNA_EXPRESS_CHECKOUT => [
            'id' => self::KLARNA_EXPRESS_CHECKOUT,
            'handlerIdentifier' => KlarnaExpressCheckoutPaymentHandler::class,
            'afterOrderEnabled' => false,
            'name' => 'Klarna Express Checkout',
            'translations' => [],
            'technicalName' => 'klarna-express-checkout'
        ],
    ];


    /** @var null|string[] */
    private array|null $availableLanguageCodes = null;

    public function __construct(
        private readonly EntityRepository $paymentMethodRepository,
        private readonly EntityRepository $salesChannelRepository,
        private readonly PluginIdProvider $pluginIdProvider,
        private readonly LanguageProvider $languageProvider
    ) {
    }

    public function install(InstallContext $context): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod, $context->getContext());
        }
    }

    public function update(UpdateContext $context): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            if (!$this->paymentMethodIsInstalled($paymentMethod['id'], $context->getContext())) {
                $this->upsertPaymentMethod($paymentMethod, $context->getContext());
            }
        }

        $this->addNewKlarnaPaymentMethodToSalesChannels($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $this->setPaymentMethodStatus($paymentMethod, false, $context->getContext());
        }
    }

    public function activate(ActivateContext $context): void
    {
        // nothing to do
    }

    public function deactivate(DeactivateContext $context): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $this->setPaymentMethodStatus($paymentMethod, false, $context->getContext());
        }
    }

    /**
     * @param array<string,mixed> $paymentMethod
     */
    private function upsertPaymentMethod(array $paymentMethod, Context $context): void
    {
        if ($this->availableLanguageCodes === null) {
            $this->availableLanguageCodes = $this->languageProvider->getAvailableLanguageCodes($context);
        }

        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(KlarnaPayment::class, $context);
        $paymentMethod['pluginId'] = $pluginId;

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($paymentMethod): void {
            $paymentMethod = $this->removeUnsupportedLanguagesFromPaymentMethod($paymentMethod);

            $this->paymentMethodRepository->upsert([$paymentMethod], $context);
        });
    }

    /**
     * @param array<string,mixed> $paymentMethod
     */
    private function setPaymentMethodStatus(array $paymentMethod, bool $active, Context $context): void
    {
        $hasPaymentMethod = $this->paymentMethodIsInstalled($paymentMethod['id'], $context);

        if (!$hasPaymentMethod) {
            return;
        }

        $data = [
            'id' => $paymentMethod['id'],
            'active' => $active,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data): void {
            $this->paymentMethodRepository->upsert([$data], $context);
        });
    }

    /**
     * @param array<string,mixed> $paymentMethod
     *
     * @return array<string,mixed>
     */
    private function removeUnsupportedLanguagesFromPaymentMethod(array $paymentMethod): array
    {
        $availablePaymentMethodTranslations = array_filter($paymentMethod['translations'], function ($localeCode) {
            return !($this->availableLanguageCodes === null || !in_array($localeCode, $this->availableLanguageCodes, true));
        }, ARRAY_FILTER_USE_KEY);

        $paymentMethod['translations'] = $availablePaymentMethodTranslations;

        return $paymentMethod;
    }

    private function paymentMethodIsInstalled(string $id, Context $context): bool
    {
        $paymentMethodCriteria = new Criteria([$id]);

        return $this->paymentMethodRepository->searchIds($paymentMethodCriteria, $context)->getTotal() > 0;
    }

    private function addNewKlarnaPaymentMethodToSalesChannels(Context $context): void
    {
        $this->setPaymentMethodStatus(self::PAYMENT_METHODS[self::KLARNA_PAY], true, $context);

        /** @var SalesChannelEntity[] $salesChannels */
        $salesChannels = $this->fetchSalesChannels($context);

        $toProcess = [];
        foreach ($salesChannels as $salesChannel) {
            $paymentMethods = $salesChannel->getPaymentMethods();

            if ($paymentMethods === null) {
                continue;
            }

            if ($paymentMethods->has(self::KLARNA_PAY)) {
                continue;
            }

            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod->getActive() && in_array($paymentMethod->getId(), self::KLARNA_DEPRECATED_IDS, true)) {
                    $toProcess[] = $salesChannel->getId();
                }
            }
        }

        $upsertData = [];

        foreach ($toProcess as $salesChannelId) {
            $upsertData[] = [
                'id' => $salesChannelId,
                'paymentMethods' => [
                    [
                        'id' => self::KLARNA_PAY,
                    ],
                ],
            ];
        }

        $this->salesChannelRepository->upsert($upsertData, $context);
    }

    private function fetchSalesChannels(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('paymentMethods');

        return $this->salesChannelRepository->search($criteria, $context)->getElements();
    }
}
