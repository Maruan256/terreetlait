<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\SynchronizationHelper;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface SynchronizationHelperInterface
{
    public function syncBillingAddress(
        OrderEntity $order,
        PaymentTransactionStruct $transaction,
        string $klarnaOrderId,
        SalesChannelContext $salesChannelContext
    ): void;
}
