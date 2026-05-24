<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\OrderDeliveryHelper;

use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class OrderDeliveryHelper implements OrderDeliveryHelperInterface
{
    /** @var EntityRepository */
    private $orderDeliveryRepository;

    public function __construct(
        EntityRepository $orderDeliveryRepository
    ) {
        $this->orderDeliveryRepository = $orderDeliveryRepository;
    }

    public function orderDoesContainRelevantShippingInformation(?OrderDeliveryEntity $orderDelivery): bool
    {
        if (!$orderDelivery || empty($orderDelivery->getTrackingCodes())) {
            return false;
        }

        if (!($order = $orderDelivery->getOrder()) || !($transactions = $order->getTransactions()) || !($transaction = $transactions->last())) {
            return false;
        }

        $state = $orderDelivery->getStateMachineState();

        if (!$state || !($customFields = $transaction->getCustomFields())) {
            return false;
        }

        if (!array_key_exists(CustomFieldInstaller::FIELD_KLARNA_ORDER_ID, $customFields) || empty($customFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID])) {
            return false;
        }

        if (!in_array($state->getTechnicalName(), [OrderDeliveryStates::STATE_SHIPPED, OrderDeliveryStates::STATE_PARTIALLY_SHIPPED])) {
            return false;
        }

        return true;
    }

    public function getOrderDeliveryById(string $orderDeliveryId, Context $context): ?OrderDeliveryEntity
    {
        $criteria = new Criteria([$orderDeliveryId]);
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('shippingMethod');
        $criteria->addAssociation('order');
        $criteria->addAssociation('order.transactions');

        return $this->orderDeliveryRepository->search($criteria, $context)->first();
    }
}
