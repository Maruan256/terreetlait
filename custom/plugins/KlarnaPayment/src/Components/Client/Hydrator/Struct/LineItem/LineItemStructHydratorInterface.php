<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\LineItem;

use KlarnaPayment\Components\Client\Struct\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection as CartLineItemCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface LineItemStructHydratorInterface
{
    /**
     * @return array<int,LineItem>
     */
    public function hydrate(CartLineItemCollection $lineItems, CurrencyEntity $currency, SalesChannelContext $salesChannelContext): array;
}
