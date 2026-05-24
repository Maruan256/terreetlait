<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\StateData;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface StateDataHelperInterface
{
    /**
     * @return null|array<string,mixed>
     */
    public function getKlarnaOrder(
        string $orderId,
        string $klarnaOrderId,
        string $salesChannelId,
        Context $context
    ): ?array;

    /**
     * @param array<string,mixed> $klarnaOrder
     */
    public function prepareDataBag(
        OrderEntity $order,
        array $klarnaOrder,
        string $salesChannelId
    ): RequestDataBag;

    public function getValidTransactions(
        OrderEntity $order
    ): OrderTransactionCollection;
}
