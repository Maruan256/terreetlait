<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Authorize;

use KlarnaPayment\Components\Helper\StateHelper\StateData\StateDataHelperInterface;
use KlarnaPayment\Core\Framework\ContextScope;
use Monolog\Logger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;

readonly class AuthorizeStateHelper implements AuthorizeStateHelperInterface
{
    public function __construct(
        private OrderTransactionStateHandler $transactionStateHandler,
        private StateDataHelperInterface $stateDataHelper,
        private Logger $logger
    ) {
    }

    public function processOrderAuthorize(OrderEntity $order, Context $context): void
    {
        foreach ($this->stateDataHelper->getValidTransactions($order) as $transaction) {
            $this->authorizeTransaction($transaction, $context);
        }
    }

    private function authorizeTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        try {
            $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($transaction): void {
                $this->transactionStateHandler->authorize($transaction->getId(), $context);
            });
        } catch (IllegalTransitionException $exception) {
            $this->logger->notice($exception->getMessage(), $exception->getParameters());
        }
    }
}
