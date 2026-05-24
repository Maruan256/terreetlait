<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Converter;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Order\IdStruct;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class CustomOrderConverter
{
    /** @var \Shopware\Core\Checkout\Cart\Order\OrderConverter */
    private $orderConverter;

    public function __construct(OrderConverter $orderConverter)
    {
        $this->orderConverter = $orderConverter;
    }

    public function convertOrderToCart(OrderEntity $order, Context $context): Cart
    {
        $cart = $this->orderConverter->convertToCart($order, $context);
        $this->addUnhydratedDataFromOrder($cart, $order);

        return $cart;
    }

    private function addUnhydratedDataFromOrder(Cart $cart, OrderEntity $orderEntity): void
    {
        $orderLineItems = $orderEntity->getLineItems();

        if ($orderLineItems === null) {
            return;
        }

        /** @var LineItem $lineItem */
        foreach ($cart->getLineItems() as $lineItem) {
            /** @var null|IdStruct $originalIdExtension */
            $originalIdExtension = $lineItem->getExtension(OrderConverter::ORIGINAL_ID);

            if ($originalIdExtension === null) {
                continue;
            }

            $originalId       = $originalIdExtension->getId();
            $originalLineItem = $orderLineItems->get($originalId);

            if ($originalLineItem !== null && $originalLineItem->getCover() !== null) {
                $lineItem->setCover($originalLineItem->getCover());
            }
        }
    }
}
