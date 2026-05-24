<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\AddShippingInfo\AddShippingInfoRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\GetOrder\GetOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Response\GetOrder\GetOrderResponseHydratorInterface;
use KlarnaPayment\Components\Exception\AddShippingInfoFailed;
use KlarnaPayment\Components\Helper\OrderDeliveryHelper\OrderDeliveryHelperInterface;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Monolog\Logger;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderDeliveriesWrittenEventListener implements EventSubscriberInterface
{
    /** @var OrderDeliveryHelperInterface */
    private $orderDeliveryHelper;

    /** @var Logger */
    private $logger;

    /** @var GetOrderRequestHydratorInterface */
    private $getOrderRequestHydrator;

    /** @var GetOrderResponseHydratorInterface */
    private $getOrderResponseHydrator;

    /** @var ClientInterface */
    private $client;

    /** @var AddShippingInfoRequestHydratorInterface */
    private $addShippingInfoRequestHydrator;

    public function __construct(
        OrderDeliveryHelperInterface $orderDeliveryHelper,
        Logger $logger,
        GetOrderRequestHydratorInterface $getOrderRequestHydrator,
        GetOrderResponseHydratorInterface $getOrderResponseHydrator,
        ClientInterface $client,
        AddShippingInfoRequestHydratorInterface $addShippingInfoRequestHydrator
    ) {
        $this->orderDeliveryHelper            = $orderDeliveryHelper;
        $this->logger                         = $logger;
        $this->getOrderRequestHydrator        = $getOrderRequestHydrator;
        $this->getOrderResponseHydrator       = $getOrderResponseHydrator;
        $this->client                         = $client;
        $this->addShippingInfoRequestHydrator = $addShippingInfoRequestHydrator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'handleOrderDeliveryChange',
        ];
    }

    public function handleOrderDeliveryChange(EntityWrittenContainerEvent $containerEvent): void
    {
        $event = $containerEvent->getEventByEntityName(OrderDeliveryDefinition::ENTITY_NAME);

        if ($event === null || $event->hasErrors() === true || $event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        // every tracking code entered will create a result containing all trackingcodes. only the last is the final result
        $writeResults = $event->getWriteResults();
        /** @var EntityWriteResult $writeResult */
        $writeResult = end($writeResults);

        $payload         = $writeResult->getPayload();
        $orderId         = $payload['orderId'] ?? null;
        $orderDeliveryId = $payload['id'] ?? null;
        $trackingCodes   = $payload['trackingCodes'] ?? null;
        $stateId         = $payload['stateId'] ?? null;

        if (!$orderDeliveryId || $writeResult->getOperation() === EntityWriteResult::OPERATION_DELETE) {
            return;
        }

        /** @var OrderDeliveryEntity $orderDelivery */
        $orderDelivery = $this->orderDeliveryHelper->getOrderDeliveryById($orderDeliveryId, $event->getContext());

        if ($this->orderDeliveryHelper->orderDoesContainRelevantShippingInformation($orderDelivery) === false) {
            return;
        }

        /**
         * orderId and trackingCodes are given when trackingCodes have been updated
         * stateId is given on stateChange
         */
        if (($orderId && !empty($trackingCodes)) || $stateId) {
            $this->updateShippingInfo($orderDelivery, $event->getContext());
        }
    }

    private function updateShippingInfo(OrderDeliveryEntity $orderDelivery, Context $context): void
    {
        /** @var OrderEntity $order */
        $order = $orderDelivery->getOrder();
        /** @var OrderTransactionCollection $transactions */
        $transactions = $order->getTransactions();
        /** @var OrderTransactionEntity $transaction */
        $transaction = $transactions->last();
        /** @var array<string,mixed> $transactionCustomFields */
        $transactionCustomFields = $transaction->getCustomFields();
        $klarnaOrderId           = $transactionCustomFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID];

        $captureId = $this->getCaptureId($order->getId(), $klarnaOrderId, $order->getSalesChannelId(), $context);

        if (!$captureId) {
            $this->logger->error('No captureId for order returned', [
                'klarnaOrderId' => $klarnaOrderId,
                'transactionId' => $transaction->getId(),
            ]);

            return;
        }

        $this->sendShippingInfo($klarnaOrderId, $captureId, $orderDelivery, $transaction, $context);
    }

    private function sendShippingInfo(
        string $klarnaOrderId,
        string $captureId,
        OrderDeliveryEntity $orderDelivery,
        OrderTransactionEntity $transaction,
        Context $context
    ): void {
        try {
            $dataBag = new RequestDataBag();
            $dataBag->add([
                'klarna_order_id' => $klarnaOrderId,
                'capture_id'      => $captureId,
            ]);

            $request = $this->addShippingInfoRequestHydrator->hydrate($dataBag, $orderDelivery, $transaction, $context);

            if (empty($request->getShippingInfos())) {
                return;
            }

            $response = $this->client->request($request, $context);

            if ($response->getHttpStatus() !== 204) {
                throw new AddShippingInfoFailed((string) $response->getHttpStatus(), $response->getResponse());
            }
        } catch (AddShippingInfoFailed $e) {
            $this->logger->error($e->getMessage(), [
                'klarnaOrderId' => $klarnaOrderId,
                'captureId'     => $captureId,
                'response'      => $response ?? null, /** @phpstan-ignore-line */
                'request'       => $request ?? null, /** @phpstan-ignore-line */
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Error while adding shipping infos', [
                'klarnaOrderId' => $klarnaOrderId,
                'captureId'     => $captureId,
                'error'         => $e->getMessage(),
                'request'       => $request ?? null,
            ]);
        }
    }

    private function getCaptureId(string $orderId, string $klarnaOrderId, string $salesChannelId, Context $context): ?string
    {
        $captureId = null;

        try {
            $dataBag = new RequestDataBag();
            $dataBag->add([
                'order_id'        => $orderId,
                'klarna_order_id' => $klarnaOrderId,
                'salesChannel'    => $salesChannelId,
            ]);

            $request  = $this->getOrderRequestHydrator->hydrate($dataBag);
            $response = $this->client->request($request, $context);

            if ($response->getHttpStatus() !== 200) {
                return null;
            }

            $order     = $this->getOrderResponseHydrator->hydrate($response, $context);
            $captureId = $order->getLastCaptureId();
        } catch (\Throwable $e) {
            $this->logger->error('Error receiving capture id', [
               'klarnaOrderId' => $klarnaOrderId,
               'error'         => $e->getMessage(),
            ]);
        }

        return $captureId;
    }
}
