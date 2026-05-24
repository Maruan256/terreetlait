<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\UpdateMerchantReferences;

use KlarnaPayment\Components\Client\Request\UpdateMerchantReferencesRequest;
use KlarnaPayment\Components\Exception\KlarnaOrderIdNotFoundException;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class UpdateMerchantReferencesRequestHydrator implements UpdateMerchantReferencesRequestHydratorInterface
{
    /** @var OrderFetcherInterface */
    private $orderFetcher;

    public function __construct(
        OrderFetcherInterface $orderFetcher
    ) {
        $this->orderFetcher = $orderFetcher;
    }

    public function hydrate(OrderEntity $orderEntity, Context $context, string $klarnaOrderId = ''): UpdateMerchantReferencesRequest
    {
        $order = $this->orderFetcher->getOrderFromOrder($orderEntity->getId(), $context);

        if ($order === null) {
            throw new \LogicException('could not find order via id');
        }

        $request = new UpdateMerchantReferencesRequest();
        $request->assign([
            'orderId'       => $orderEntity->getId(),
            'klarnaOrderId' => $klarnaOrderId ?: $this->getKlarnaOrderId($orderEntity),
            'salesChannel'  => $orderEntity->getSalesChannelId(),
            'merchantReference1' => $orderEntity->getOrderNumber() ?: "",
            'merchantReference2' => ""
        ]);

        return $request;
    }

    private function getKlarnaOrderId(OrderEntity $orderEntity): string
    {
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
