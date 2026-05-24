<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\PaymentHelper;

use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface PaymentHelperInterface
{
    public function isKlarnaPaymentsEnabled(SalesChannelContext $context): bool;

    public function isKlarnaExpressCheckoutEnabled(SalesChannelContext $context): bool;

    public function isKlarnaPaymentsSelected(SalesChannelContext $context): bool;

    /**
     * @return string[]
     */
    public function getKlarnaPaymentMethodIds(): array;

    public function getShippingCountry(SalesChannelContext $context): CountryEntity;

    public function getSalesChannelLocale(SalesChannelContext $context): LocaleEntity;
}
