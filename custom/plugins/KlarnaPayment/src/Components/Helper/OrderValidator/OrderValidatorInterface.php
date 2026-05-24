<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\OrderValidator;

use KlarnaPayment\Components\Exception\GetKlarnaOrderException;
use KlarnaPayment\Components\Exception\KlarnaOrderIdNotFoundException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface OrderValidatorInterface
{
    public function isKlarnaOrder(OrderEntity $orderEntity): bool;

    /**
     * @throws GetKlarnaOrderException
     * @throws KlarnaOrderIdNotFoundException
     */
    public function validateAndInitLineItemsHash(OrderEntity $orderEntity, Context $context): bool;

    /**
     * @throws GetKlarnaOrderException
     * @throws KlarnaOrderIdNotFoundException
     */
    public function validateAndInitOrderAddressHash(OrderEntity $orderEntity, OrderEntity|null $previousOrder, Context $context, array &$errorArray = []): bool;
}
