<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\Delivery;

use KlarnaPayment\Components\Client\Struct\LineItem;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Currency\CurrencyEntity;

interface DeliveryStructHydratorInterface
{
    /**
     * @return LineItem[]
     */
    public function hydrate(DeliveryCollection $deliveries, CurrencyEntity $currency, Context $context): array;
}
