<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\StateData;

use KlarnaPayment\Components\Client\Client;
use KlarnaPayment\Components\Client\Hydrator\Request\GetOrder\GetOrderRequestHydratorInterface as Hydrator;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class StateDataHelper implements StateDataHelperInterface
{
    /** @var Hydrator */
    private $hydrator;

    /** @var Client */
    private $client;

    public function __construct(
        Hydrator $hydrator,
        Client $client
    ) {
        $this->hydrator = $hydrator;
        $this->client   = $client;
    }

    /**
     * @return null|array<string,mixed>
     */
    public function getKlarnaOrder(
        string $orderId,
        string $klarnaOrderId,
        string $salesChannelId,
        Context $context
    ): ?array {
        $dataBag = new RequestDataBag();
        $dataBag->set('order_id', $orderId);
        $dataBag->set('klarna_order_id', $klarnaOrderId);
        $dataBag->set('salesChannel', $salesChannelId);

        $request  = $this->hydrator->hydrate($dataBag);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() !== 200) {
            return null;
        }

        return $response->getResponse();
    }

    public function prepareDataBag(
        OrderEntity $order,
        array $klarnaOrder,
        string $salesChannelId
    ): RequestDataBag {
        $dataBag = new RequestDataBag();
        $dataBag->add([
            'klarna_order_id' => $klarnaOrder['order_id'],
            'salesChannel'    => $salesChannelId,
            'orderLines'      => json_encode($klarnaOrder['order_lines']),
            'description'     => null,
        ]);

        return $dataBag;
    }

    public function getValidTransactions(OrderEntity $order): OrderTransactionCollection
    {
        if ($order->getTransactions() === null) {
            return new OrderTransactionCollection();
        }

        return $order->getTransactions()->filter(static function (OrderTransactionEntity $transaction) {
            if ($transaction->getStateMachineState() === null) {
                return false;
            }

            if ($transaction->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_CANCELLED) {
                return false;
            }

            return true;
        });
    }
}
