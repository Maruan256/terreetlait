<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\UpdateOrder;

use KlarnaPayment\Components\Client\Request\UpdateOrderRequest;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface UpdateOrderRequestHydratorInterface
{
    public function hydrate(OrderEntity $orderEntity, Context $context): UpdateOrderRequest;
}
