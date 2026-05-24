<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Refund;

use KlarnaPayment\Components\Client\Client;
use KlarnaPayment\Components\Client\Hydrator\Request\CreateRefund\CreateRefundRequestHydratorInterface;
use KlarnaPayment\Components\Helper\StateHelper\StateData\StateDataHelperInterface;
use KlarnaPayment\Core\Framework\ContextScope;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Monolog\Logger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;

class RefundStateHelper implements RefundStateHelperInterface
{
    /** @var CreateRefundRequestHydratorInterface */
    private $refundRequestHydrator;

    /** @var OrderTransactionStateHandler */
    private $transactionStateHandler;

    /** @var StateDataHelperInterface */
    private $stateDataHelper;

    /** @var Logger */
    private $logger;

    /** @var Client */
    private $client;

    public function __construct(
        CreateRefundRequestHydratorInterface $refundRequestHydrator,
        OrderTransactionStateHandler $transactionStateHandler,
        StateDataHelperInterface $stateDataHelper,
        Logger $logger,
        Client $client
    ) {
        $this->refundRequestHydrator   = $refundRequestHydrator;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->stateDataHelper         = $stateDataHelper;
        $this->client                  = $client;
        $this->logger                  = $logger;
    }

    public function processOrderRefund(OrderEntity $order, Context $context): void
    {
        foreach ($this->stateDataHelper->getValidTransactions($order) as $transaction) {
            $this->refundTransaction($transaction, $order, $context);
        }
    }

    private function refundTransaction(OrderTransactionEntity $transaction, OrderEntity $order, Context $context): void
    {
        if ($order->getCurrency() === null) {
            return;
        }

        $customFields = $transaction->getCustomFields();

        if (empty($customFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID])) {
            return;
        }

        $klarnaOrder = $this->stateDataHelper->getKlarnaOrder(
            $order->getId(),
            $customFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID],
            $order->getSalesChannelId(),
            $context
        );

        if (empty($klarnaOrder)) {
            return;
        }

        if ($klarnaOrder['captured_amount'] <= 0) {
            return;
        }

        $refundAmount = (int) ($klarnaOrder['captured_amount'] - $klarnaOrder['refunded_amount']);

        if ($refundAmount <= 0) {
            return;
        }

        $dataBag = $this->stateDataHelper->prepareDataBag(
            $order,
            $klarnaOrder,
            $order->getSalesChannelId()
        );

        $dataBag->set('refundAmount', $refundAmount / 100);

        $request  = $this->refundRequestHydrator->hydrate($dataBag, $context);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() !== 201) {
            $this->logger->notice('transaction was not refunded automatically', [
                'orderNumber'   => $order->getOrderNumber(),
                'transactionId' => $transaction->getId(),
            ]);

            return;
        }

        try {
            $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($transaction): void {
                $this->transactionStateHandler->refund($transaction->getId(), $context);
            });
        } catch (IllegalTransitionException $exception) {
            $this->logger->notice($exception->getMessage(), $exception->getParameters());
        }
    }
}
