<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\UpdateOrder;

use KlarnaPayment\Components\Client\Hydrator\Struct\Delivery\DeliveryStructHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\LineItem\LineItemStructHydratorInterface;
use KlarnaPayment\Components\Client\Request\UpdateOrderRequest;
use KlarnaPayment\Components\Converter\CustomOrderConverter;
use KlarnaPayment\Components\Exception\KlarnaOrderIdNotFoundException;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;

class UpdateOrderRequestHydrator implements UpdateOrderRequestHydratorInterface
{
    /** @var CustomOrderConverter */
    private $orderConverter;

    /** @var OrderFetcherInterface */
    private $orderFetcher;

    /** @var LineItemStructHydratorInterface */
    private $lineItemHydrator;

    /** @var DeliveryStructHydratorInterface */
    private $deliveryHydrator;

    /** @var AbstractSalesChannelContextFactory|SalesChannelContextFactory */
    private $salesChannelContextFactory;

    /**
     * Due to the release of AbstractSalesChannelContextFactory in v6.4.0.0
     * we can't provide a typehint for salesChannelContextFactory
     *
     * @param AbstractSalesChannelContextFactory|SalesChannelContextFactory $salesChannelContextFactory
     */
    public function __construct(
        CustomOrderConverter $orderConverter,
        OrderFetcherInterface $orderFetcher,
        LineItemStructHydratorInterface $lineItemHydrator,
        DeliveryStructHydratorInterface $deliveryHydrator,
        $salesChannelContextFactory
    ) {
        $this->orderConverter             = $orderConverter;
        $this->orderFetcher               = $orderFetcher;
        $this->lineItemHydrator           = $lineItemHydrator;
        $this->deliveryHydrator           = $deliveryHydrator;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
    }

    public function hydrate(OrderEntity $orderEntity, Context $context): UpdateOrderRequest
    {
        if ($orderEntity->getCurrency() === null) {
            throw new \LogicException('could not find order currency');
        }

        $order = $this->orderFetcher->getOrderFromOrder($orderEntity->getId(), $context);

        if ($order === null) {
            throw new \LogicException('could not find order via id');
        }

        $cart      = $this->orderConverter->convertOrderToCart($order, $context);
        $lineItems = $this->hydrateOrderLines($cart, $orderEntity, $context);

        $request = new UpdateOrderRequest();
        $request->assign([
            'orderId'       => $orderEntity->getId(),
            'klarnaOrderId' => $this->getKlarnaOrderId($orderEntity),
            'salesChannel'  => $orderEntity->getSalesChannelId(),
            'lineItems'     => $lineItems,
            'orderAmount'   => $orderEntity->getPrice()->getTotalPrice(),
        ]);

        return $request;
    }

    /**
     * @return array<int,mixed>
     */
    private function hydrateOrderLines(Cart $cart, OrderEntity $order, Context $context): array
    {
        $orderLines = [];

        /** @var \Shopware\Core\System\Currency\CurrencyEntity $currency */
        $currency   = $order->getCurrency();
        $lineItems  = $cart->getLineItems();
        $deliveries = $cart->getDeliveries();

        $salesChannelContext = $this->salesChannelContextFactory
            ->create(Uuid::randomHex(), $order->getSalesChannelId());

        foreach ($this->lineItemHydrator->hydrate($lineItems, $currency, $salesChannelContext) as $orderLine) {
            $orderLines[] = $orderLine;
        }

        foreach ($this->deliveryHydrator->hydrate($deliveries, $currency, $context) as $orderLine) {
            $orderLines[] = $orderLine;
        }

        return array_filter($orderLines);
    }

    private function getKlarnaOrderId(OrderEntity $orderEntity): string
    {
        if ($orderEntity->getTransactions() === null) {
            throw new KlarnaOrderIdNotFoundException();
        }

        foreach ($orderEntity->getTransactions() as $transaction) {
            if ($transaction->getStateMachineState() === null) {
                continue;
            }

            if ($transaction->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_CANCELLED) {
                continue;
            }

            if (empty($transaction->getCustomFields()[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID])) {
                continue;
            }

            return $transaction->getCustomFields()[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID];
        }

        throw new KlarnaOrderIdNotFoundException();
    }
}
