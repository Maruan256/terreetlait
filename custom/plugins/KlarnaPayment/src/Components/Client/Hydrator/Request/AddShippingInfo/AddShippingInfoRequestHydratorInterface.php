<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\AddShippingInfo;

use KlarnaPayment\Components\Client\Request\AddShippingInfoRequest;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface AddShippingInfoRequestHydratorInterface
{
    public function hydrate(
        RequestDataBag $dataBag,
        OrderDeliveryEntity $orderDelivery,
        OrderTransactionEntity $transaction,
        Context $context
    ): AddShippingInfoRequest;
}
