<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\SalesTaxLineItem;

use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection as CartLineItemCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface SalesTaxLineItemStructHydratorInterface
{
    public function hydrate(CartLineItemCollection $lineItems, DeliveryCollection $deliveries, CurrencyEntity $currency, SalesChannelContext $salesChannelContext): array;
}
