<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\AddShippingInfo;

use KlarnaPayment\Components\Client\Request\AddShippingInfoRequest;
use KlarnaPayment\Components\Event\AddCaptureShippingInfo;
use KlarnaPayment\Components\Struct\ShippingInfo;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddShippingInfoRequestHydrator implements AddShippingInfoRequestHydratorInterface
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function hydrate(
        RequestDataBag $dataBag,
        OrderDeliveryEntity $orderDelivery,
        OrderTransactionEntity $transaction,
        Context $context
    ): AddShippingInfoRequest {
        $request = new AddShippingInfoRequest();

        $request->assign([
            'orderId'       => $dataBag->get('order_id'),
            'klarnaOrderId' => $dataBag->get('klarna_order_id'),
            'captureId'     => $dataBag->get('capture_id'),
            'shippingInfos' => $this->hydrateShippingInfos($orderDelivery, $transaction, $context),
        ]);

        return $request;
    }

    /**
     * @return ShippingInfo[]
     */
    private function hydrateShippingInfos(
        OrderDeliveryEntity $orderDelivery,
        OrderTransactionEntity $transaction,
        Context $context
    ): array {
        /** @var ShippingMethodEntity $shippingMethod */
        $shippingMethod = $orderDelivery->getShippingMethod();

        $shippingInfos = [];

        foreach ($orderDelivery->getTrackingCodes() as $trackingCode) {
            $data = new ShippingInfo();
            $data->assign([
                'trackingNumber' => $trackingCode,
                'trackingUri'    => $shippingMethod->getTrackingUrl(),
            ]);

            $event = new AddCaptureShippingInfo(
                $data,
                $orderDelivery->getOrderId(),
                $transaction->getId(),
                $orderDelivery->getShippingMethodId(),
                $context
            );

            $this->eventDispatcher->dispatch($event);

            if (!$event->getData()->getTrackingNumber()) {
                continue;
            }

            $shippingInfos[] = $event->getData();
        }

        return $shippingInfos;
    }
}
