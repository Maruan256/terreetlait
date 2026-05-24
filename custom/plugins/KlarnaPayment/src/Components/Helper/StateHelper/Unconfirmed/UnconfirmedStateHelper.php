<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Unconfirmed;

use KlarnaPayment\Components\Helper\StateHelper\StateData\StateDataHelperInterface;
use KlarnaPayment\Core\Framework\ContextScope;

use Monolog\Logger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;

class UnconfirmedStateHelper implements UnconfirmedStateHelperInterface
{
    /** @var OrderTransactionStateHandler */
    private $transactionStateHandler;

    /** @var StateDataHelperInterface */
    private $stateDataHelper;

    /** @var Logger */
    private $logger;

    public function __construct(
        OrderTransactionStateHandler $transactionStateHandler,
        StateDataHelperInterface $stateDataHelper,
        Logger $logger,
    ) {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->stateDataHelper         = $stateDataHelper;
        $this->logger                  = $logger;
    }

    public function processOrderUnconfirmation(OrderEntity $order, Context $context): void
    {
        foreach ($this->stateDataHelper->getValidTransactions($order) as $transaction) {
            $this->unconfirmedTransaction($transaction, $context);
        }
    }

    public function processOrderTransactionUnconfirmation(OrderTransactionEntity $transaction, Context $context): void
    {
        $this->unconfirmedTransaction($transaction, $context);
    }

    private function unconfirmedTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        try {
            $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($transaction): void {
                $this->transactionStateHandler->processUnconfirmed($transaction->getId(), $context);
            });
        } catch (IllegalTransitionException $exception) {
            $this->logger->notice($exception->getMessage(), $exception->getParameters());
        }
    }
}
