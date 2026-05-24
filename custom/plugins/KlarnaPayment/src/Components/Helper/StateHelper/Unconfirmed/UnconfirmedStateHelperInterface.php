<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Unconfirmed;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface UnconfirmedStateHelperInterface
{
    public function processOrderUnconfirmation(OrderEntity $order, Context $context): void;

    public function processOrderTransactionUnconfirmation(OrderTransactionEntity $transaction, Context $context): void;
}
