<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\Helper\OrderFetcher;
use KlarnaPayment\Components\Helper\OrderValidator\OrderValidatorInterface;
use KlarnaPayment\Components\Helper\StateHelper\Cancel\CancelStateHelper;
use KlarnaPayment\Components\Helper\StateHelper\Capture\CaptureStateHelperInterface;
use KlarnaPayment\Components\Helper\StateHelper\Refund\RefundStateHelperInterface;
use KlarnaPayment\Components\Helper\StateHelper\StateData\StateDataHelper;
use KlarnaPayment\Components\PaymentHandler\AbstractKlarnaPaymentHandler;
use KlarnaPayment\Components\Validator\OrderTransitionChangeValidator;
use KlarnaPayment\Core\Framework\ContextScope;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStatusTransitionEventListener implements EventSubscriberInterface
{
    /** @var OrderTransitionChangeValidator */
    private $orderStatusValidator;

    /** @var CaptureStateHelperInterface */
    private $captureStateHelper;

    /** @var RefundStateHelperInterface */
    private $refundStateHelper;

    /** @var OrderFetcher */
    private $orderFetcher;

    /** @var CancelStateHelper */
    private $cancelStateHelper;

    /** @var StateDataHelper */
    private $stateDataHelper;

    /** @var OrderValidatorInterface */
    private $orderValidator;

    public function __construct(
        OrderTransitionChangeValidator $orderStatusValidator,
        CaptureStateHelperInterface $captureStateHelper,
        RefundStateHelperInterface $refundStateHelper,
        CancelStateHelper $cancelStateHelper,
        OrderFetcher $orderFetcher,
        StateDataHelper $stateDataHelper,
        OrderValidatorInterface $orderValidator
    ) {
        $this->orderStatusValidator = $orderStatusValidator;
        $this->captureStateHelper   = $captureStateHelper;
        $this->refundStateHelper    = $refundStateHelper;
        $this->cancelStateHelper    = $cancelStateHelper;
        $this->orderFetcher         = $orderFetcher;
        $this->stateDataHelper      = $stateDataHelper;
        $this->orderValidator       = $orderValidator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StateMachineTransitionEvent::class => 'onStateMachineTransition',
        ];
    }

    public function onStateMachineTransition(StateMachineTransitionEvent $transitionEvent): void
    {
        $context = $transitionEvent->getContext();

        if ($transitionEvent->getContext()->getScope() === ContextScope::INTERNAL_SCOPE) {
            return;
        }

        $order = $this->getOrder($transitionEvent, $context);

        if (!$order || !$this->orderValidator->isKlarnaOrder($order)) {
            return;
        }

        if ($this->isFraudStatusAccepted($order)
            && $this->orderStatusValidator->isAutomaticCapture(
                $transitionEvent->getEntityName(),
                $transitionEvent->getToPlace()->getTechnicalName(),
                $order->getSalesChannelId(),
                $transitionEvent->getContext()
            )
        ) {
            $this->captureStateHelper->processOrderCapture($order, $context);
        } elseif ($this->orderStatusValidator->isAutomaticRefund($transitionEvent, $order->getSalesChannelId())) {
            $this->refundStateHelper->processOrderRefund($order, $context);
        }

        if($transitionEvent->getFromPlace()->getId() !== $transitionEvent->getToPlace()->getId()) {
            if ($this->orderStatusValidator->isAutomaticCancel($transitionEvent)) {
                if ($transitionEvent->getEntityName() === OrderDefinition::ENTITY_NAME) {
                    $this->cancelStateHelper->processOrderCancellation($order, $context);
                } elseif ($transitionEvent->getEntityName() === OrderTransactionDefinition::ENTITY_NAME) {
                    $this->cancelTransaction($order, $transitionEvent->getEntityId(), $context);
                }
            }
        }
    }

    private function isFraudStatusAccepted(OrderEntity $orderEntity): bool
    {
        foreach ($this->stateDataHelper->getValidTransactions($orderEntity) as $transaction) {
            if (is_array($transaction->getCustomFields())
                && isset($transaction->getCustomFields()[CustomFieldInstaller::FIELD_KLARNA_FRAUD_STATUS])
                && strtolower($transaction->getCustomFields()[CustomFieldInstaller::FIELD_KLARNA_FRAUD_STATUS]) === strtolower(AbstractKlarnaPaymentHandler::FRAUD_STATUS_ACCEPTED)) {
                return true;
            }
        }

        return false;
    }

    private function getOrder(StateMachineTransitionEvent $transitionEvent, Context $context): ?OrderEntity
    {
        if ($transitionEvent->getEntityName() === OrderDefinition::ENTITY_NAME) {
            return $this->orderFetcher->getOrderFromOrder($transitionEvent->getEntityId(), $context);
        }

        if ($transitionEvent->getEntityName() === OrderDeliveryDefinition::ENTITY_NAME) {
            return $this->orderFetcher->getOrderFromOrderDelivery($transitionEvent->getEntityId(), $context);
        }

        if ($transitionEvent->getEntityName() === OrderTransactionDefinition::ENTITY_NAME) {
            return $this->orderFetcher->getOrderFromOrderTransaction($transitionEvent->getEntityId(), $context);
        }

        return null;
    }

    private function cancelTransaction(OrderEntity $order, string $transactionId, Context $context): void
    {
        if ($order->getTransactions() === null) {
            return;
        }

        $transaction = $order->getTransactions()->get($transactionId);

        if ($transaction === null) {
            return;
        }

        $this->cancelStateHelper->processOrderTransactionCancellation($transaction, $order, $context);
    }
}
