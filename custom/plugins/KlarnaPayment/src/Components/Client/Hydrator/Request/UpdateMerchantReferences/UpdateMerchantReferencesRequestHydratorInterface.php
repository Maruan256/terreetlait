<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\UpdateMerchantReferences;

use KlarnaPayment\Components\Client\Request\UpdateMerchantReferencesRequest;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface UpdateMerchantReferencesRequestHydratorInterface
{
    public function hydrate(OrderEntity $orderEntity, Context $context, string $klarnaOrderId = ''): UpdateMerchantReferencesRequest;
}
