<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Cancel;

use KlarnaPayment\Components\Client\Client;
use KlarnaPayment\Components\Client\Hydrator\Request\CancelPayment\CancelPaymentRequestHydratorInterface;
use KlarnaPayment\Components\Helper\StateHelper\StateData\StateDataHelperInterface;
use KlarnaPayment\Core\Framework\ContextScope;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Monolog\Logger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;

class CancelStateHelper implements CancelStateHelperInterface
{
    /** @var CancelPaymentRequestHydratorInterface */
    private $cancelRequestHydrator;

    /** @var OrderTransactionStateHandler */
    private $transactionStateHandler;

    /** @var StateDataHelperInterface */
    private $stateDataHelper;

    /** @var Logger */
    private $logger;

    /** @var Client */
    private $client;

    public function __construct(
        CancelPaymentRequestHydratorInterface $cancelRequestHydrator,
        OrderTransactionStateHandler $transactionStateHandler,
        StateDataHelperInterface $stateDataHelper,
        Logger $logger,
        Client $client
    ) {
        $this->cancelRequestHydrator   = $cancelRequestHydrator;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->stateDataHelper         = $stateDataHelper;
        $this->logger                  = $logger;
        $this->client                  = $client;
    }

    public function processOrderCancellation(OrderEntity $order, Context $context): void
    {
        foreach ($this->stateDataHelper->getValidTransactions($order) as $transaction) {
            $this->cancelTransaction($transaction, $order, $context);
        }
    }

    public function processOrderTransactionCancellation(OrderTransactionEntity $transaction, OrderEntity $order, Context $context): void
    {
        $this->cancelTransaction($transaction, $order, $context);
    }

    private function cancelTransaction(OrderTransactionEntity $transaction, OrderEntity $order, Context $context): void
    {
        $customFields = $transaction->getCustomFields();

        if (empty($customFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID])) {
            return;
        }

        $dataBag = new RequestDataBag();
        $dataBag->add([
            'klarna_order_id' => $customFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID],
            'salesChannel'    => $order->getSalesChannelId(),
        ]);

        $request  = $this->cancelRequestHydrator->hydrate($dataBag);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() !== 204) {
            $this->logger->notice('transaction was not cancelled automatically', [
                'orderNumber'   => $order->getOrderNumber(),
                'transactionId' => $transaction->getId(),
            ]);

            return;
        }

        try {
            $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($transaction): void {
                $this->transactionStateHandler->cancel($transaction->getId(), $context);
            });
        } catch (IllegalTransitionException $exception) {
            $this->logger->notice($exception->getMessage(), $exception->getParameters());
        }
    }
}
