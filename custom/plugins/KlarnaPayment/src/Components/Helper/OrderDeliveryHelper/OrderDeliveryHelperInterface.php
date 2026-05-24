<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\OrderDeliveryHelper;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Framework\Context;

interface OrderDeliveryHelperInterface
{
    public function orderDoesContainRelevantShippingInformation(?OrderDeliveryEntity $orderDelivery): bool;

    public function getOrderDeliveryById(string $orderDeliveryId, Context $context): ?OrderDeliveryEntity;
}
