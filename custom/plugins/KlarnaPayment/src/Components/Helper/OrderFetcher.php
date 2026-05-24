<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper;

use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtension;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory; // 6.4
use Shopware\Core\System\SalesChannel\SalesChannelContext; // 6.3

class OrderFetcher implements OrderFetcherInterface
{
    /** @var EntityRepository */
    private $orderRepository;

    /**
     * @phpstan-ignore-next-line
     *
     * @var AbstractSalesChannelContextFactory|SalesChannelContextFactory
     */
    private $salesChannelContextFactory;

    /** @phpstan-ignore-next-line */
    public function __construct(EntityRepository $orderRepository, $salesChannelContextFactory)
    {
        $this->orderRepository            = $orderRepository;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
    }

    public function getOrderFromOrderAddress(string $orderAddressId, Context $context): ?OrderEntity
    {
        if (\mb_strlen($orderAddressId, '8bit') === 16) {
            $orderAddressId = Uuid::fromBytesToHex($orderAddressId);
        }

        // Get sales channel id from order to get the order with the correct languages in the context
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('addresses.id', $orderAddressId));

        $order = $this->orderRepository->search($criteria, $context)->first();

        if (!$order instanceof OrderEntity) {
            return null;
        }

        $salesChannelContext = $this->getSalesChannelContext($order->getSalesChannelId());

        $criteria = $this->getOrderCriteria();
        $criteria->addFilter(new EqualsFilter('addresses.id', $orderAddressId));

        return $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
    }

    public function getOrderFromOrderLineItem(string $lineItemId, Context $context): ?OrderEntity
    {
        if (\mb_strlen($lineItemId, '8bit') === 16) {
            $lineItemId = Uuid::fromBytesToHex($lineItemId);
        }

        // Get sales channel id from order to get the order with the correct languages in the context
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('lineItems.id', $lineItemId));

        $order = $this->orderRepository->search($criteria, $context)->first();

        if (!$order instanceof OrderEntity) {
            return null;
        }

        $salesChannelContext = $this->getSalesChannelContext($order->getSalesChannelId());

        $criteria = $this->getOrderCriteria();
        $criteria->addFilter(new EqualsFilter('lineItems.id', $lineItemId));

        return $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
    }

    public function getOrderFromOrder(string $orderId, Context $context): ?OrderEntity
    {
        if (\mb_strlen($orderId, '8bit') === 16) {
            $orderId = Uuid::fromBytesToHex($orderId);
        }

        // Get sales channel id from order to get the order with the correct languages in the context
        $criteria = new Criteria([$orderId]);
        $order    = $this->orderRepository->search($criteria, $context)->first();

        if (!$order instanceof OrderEntity) {
            return null;
        }

        $salesChannelContext = $this->getSalesChannelContext($order->getSalesChannelId());

        $criteria = $this->getOrderCriteria();
        $criteria->addFilter(new EqualsFilter('id', $orderId));

        return $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
    }

    public function getOrderFromOrderTransaction(string $transactionId, Context $context): ?OrderEntity
    {
        if (\mb_strlen($transactionId, '8bit') === 16) {
            $transactionId = Uuid::fromBytesToHex($transactionId);
        }

        // Get sales channel id from order to get the order with the correct languages in the context
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('transactions.id', $transactionId));

        $order = $this->orderRepository->search($criteria, $context)->first();

        if (!$order instanceof OrderEntity) {
            return null;
        }

        $salesChannelContext = $this->getSalesChannelContext($order->getSalesChannelId());

        $criteria = $this->getOrderCriteria();
        $criteria->addFilter(new EqualsFilter('transactions.id', $transactionId));

        return $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
    }

    public function getOrderFromOrderDelivery(string $deliveryId, Context $context): ?OrderEntity
    {
        if (\mb_strlen($deliveryId, '8bit') === 16) {
            $deliveryId = Uuid::fromBytesToHex($deliveryId);
        }

        // Get sales channel id from order to get the order with the correct languages in the context
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('deliveries.id', $deliveryId));

        $order = $this->orderRepository->search($criteria, $context)->first();

        if (!$order instanceof OrderEntity) {
            return null;
        }

        $salesChannelContext = $this->getSalesChannelContext($order->getSalesChannelId());

        $criteria = $this->getOrderCriteria();
        $criteria->addFilter(new EqualsFilter('deliveries.id', $deliveryId));

        return $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
    }

    private function getOrderCriteria(): Criteria
    {
        $criteria = new Criteria();
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('transactions.stateMachineState');
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('addresses.salutation');
        $criteria->addAssociation('addresses.country');
        $criteria->addAssociation('billingAddress.country');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('deliveries.shippingMethod');
        $criteria->addAssociation('deliveries.positions');
        $criteria->addAssociation('deliveries.positions.orderLineItem');
        $criteria->addAssociation('deliveries.shippingOrderAddress');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('deliveries.shippingOrderAddress.salutation');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('lineItems.cover');
        $criteria->addAssociation('currency');
        $criteria->addAssociation(OrderExtension::EXTENSION_NAME);
        $criteria->addSorting(new FieldSorting('lineItems.createdAt'));

        return $criteria;
    }

    private function getSalesChannelContext(string $salesChannelId): SalesChannelContext
    {
        /** @phpstan-ignore-next-line */
        return $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelId);
    }
}
