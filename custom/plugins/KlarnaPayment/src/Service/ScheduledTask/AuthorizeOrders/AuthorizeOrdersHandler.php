<?php /** @noinspection PhpInternalEntityUsedInspection */

declare(strict_types=1);

namespace KlarnaPayment\Service\ScheduledTask\AuthorizeOrders;

use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\GetOrder\GetOrderRequestHydratorInterface;
use KlarnaPayment\Components\Exception\GetKlarnaOrderException;
use KlarnaPayment\Components\Exception\KlarnaOrderIdNotFoundException;
use KlarnaPayment\Components\Helper\StateHelper\Authorize\AuthorizeStateHelper;
use KlarnaPayment\Components\PaymentHandler\AbstractKlarnaPaymentHandler;
use KlarnaPayment\Core\Framework\ContextScope;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface as PsrLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Flow\Dispatching\Action\SendMailAction;
use Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler(handles: AuthorizeOrdersTask::class)]
class AuthorizeOrdersHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        PsrLogger $logger,
        private readonly EntityRepository $orderRepository,
        private readonly ClientInterface $client,
        private readonly GetOrderRequestHydratorInterface $getOrderRequestHydrator,
        private readonly AuthorizeStateHelper $authorizeStateHelper,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly MonologLogger $klarnaLogger
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    /** @noinspection PhpInternalEntityUsedInspection */
    public function run(): void
    {
        $context = Context::createDefaultContext();
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('transactions.paymentMethodId', PaymentMethodInstaller::KLARNA_PAY))
            ->addFilter(new EqualsFilter('transactions.stateMachineState.technicalName', OrderTransactionStates::STATE_OPEN))
            ->addAssociation('transactions')
            ->addAssociation('transactions.stateMachineState');

        foreach ($this->orderRepository->search($criteria, $context)->getEntities() as $orderEntity) {
            try {
                $klarnaOrderStatus = $this->getOrderStatus($orderEntity, $context);

                if ($klarnaOrderStatus) {
                    match ($klarnaOrderStatus) {
                        AbstractKlarnaPaymentHandler::ORDER_STATUS_CLOSED,
                        AbstractKlarnaPaymentHandler::ORDER_STATUS_CANCELLED => $this->cancelLastTransactionLocally($orderEntity, $context),
                        AbstractKlarnaPaymentHandler::ORDER_STATUS_AUTHORIZED,
                        AbstractKlarnaPaymentHandler::ORDER_STATUS_PART_CAPTURED,
                        AbstractKlarnaPaymentHandler::ORDER_STATUS_CAPTURED,
                        AbstractKlarnaPaymentHandler::ORDER_STATUS_EXPIRED => $this->authorizeStateHelper->processOrderAuthorize($orderEntity, $context),
                    };
                }
            } catch (GetKlarnaOrderException|KlarnaOrderIdNotFoundException) {
                $this->cancelLastTransactionLocally($orderEntity, $context);
            } catch (Throwable $exception) {
                $this->klarnaLogger->error($exception->getMessage(), $exception->getTrace());
            }
        }
    }

    /**
     * @throws KlarnaOrderIdNotFoundException
     * @throws GetKlarnaOrderException
     */
    private function getOrderStatus(OrderEntity $orderEntity, Context $context): ?string
    {
        if ($transaction = $orderEntity->getTransactions()?->last()) {
            $klarnaOrderId = $transaction->getCustomFieldsValue(CustomFieldInstaller::FIELD_KLARNA_ORDER_ID);

            if (empty($klarnaOrderId)) {
                throw new KlarnaOrderIdNotFoundException();
            }

            $dataBag = new RequestDataBag();
            $dataBag->add([
                'order_id' => $orderEntity->getId(),
                'klarna_order_id' => $klarnaOrderId,
                'salesChannel' => $orderEntity->getSalesChannelId(),
            ]);

            $response = $this->client->request($this->getOrderRequestHydrator->hydrate($dataBag), $context);

            if ($response->getHttpStatus() !== 200) {
                throw new GetKlarnaOrderException((string)$response->getHttpStatus(), []);
            }

            return $response->getResponse()["status"] ?? null;
        }

        return null;
    }

    private function cancelLastTransactionLocally(OrderEntity $orderEntity, Context $context): void
    {
        $transaction = $orderEntity->getTransactions()?->last();

        if ($transaction === null || empty($transaction->getId())) {
            return;
        }

        try {
            $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($transaction): void {
                $context->addExtension(
                    SendMailAction::MAIL_CONFIG_EXTENSION,
                    new MailSendSubscriberConfig(true, [], [])
                );
                
                $this->transactionStateHandler->cancel($transaction->getId(), $context);
            });
        } catch (InconsistentCriteriaIdsException|IllegalTransitionException|StateMachineException $exception) {
            $this->klarnaLogger->notice($exception->getMessage());
        }
    }
}