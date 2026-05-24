<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\UpdateAddress;

use KlarnaPayment\Components\Client\Request\UpdateAddressRequest;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface UpdateAddressRequestHydratorInterface
{
    public function hydrate(OrderEntity $orderEntity, Context $context): UpdateAddressRequest;
}
