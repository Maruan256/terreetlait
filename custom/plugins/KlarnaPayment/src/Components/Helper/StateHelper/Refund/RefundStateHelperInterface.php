<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Refund;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface RefundStateHelperInterface
{
    public function processOrderRefund(OrderEntity $order, Context $context): void;
}
