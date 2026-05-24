<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Cancel;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface CancelStateHelperInterface
{
    public function processOrderCancellation(OrderEntity $order, Context $context): void;

    public function processOrderTransactionCancellation(OrderTransactionEntity $transaction, OrderEntity $order, Context $context): void;
}
