<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\PaymentHandler;

use JsonException;
use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\CreateOrder\CreateOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateAddress\UpdateAddressRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateOrder\UpdateOrderRequestHydratorInterface;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtension;
use KlarnaPayment\Components\Helper\Hpp\HppHelper;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Components\Helper\RequestHasherInterface;
use KlarnaPayment\Components\Helper\StateHelper\Authorize\AuthorizeStateHelperInterface;
use KlarnaPayment\Components\Helper\StateHelper\Capture\CaptureStateHelperInterface;
use KlarnaPayment\Components\Helper\SynchronizationHelper\SynchronizationHelperInterface;
use KlarnaPayment\Components\Validator\OrderTransitionChangeValidator;
use KlarnaPayment\Core\Framework\ContextScope;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Monolog\Logger;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class KlarnaPaymentsPaymentHandler extends AbstractKlarnaPaymentHandler
{
    public function __construct(
        private readonly CreateOrderRequestHydratorInterface $requestHydrator,
        private readonly ClientInterface $client,
        protected EntityRepository $transactionRepository,
        private readonly EntityRepository $orderRepository,
        private readonly TranslatorInterface $translator,
        private readonly RequestHasherInterface $updateOrderRequestHasher,
        private readonly RequestHasherInterface $updateAddressRequestHasher,
        private readonly UpdateAddressRequestHydratorInterface $addressRequestHydrator,
        private readonly UpdateOrderRequestHydratorInterface $orderRequestHydrator,
        private readonly OrderFetcherInterface $orderFetcher,
        protected RequestStack $requestStack,
        private readonly OrderTransitionChangeValidator $orderStatusValidator,
        private readonly CaptureStateHelperInterface $captureStateHelper,
        private readonly AuthorizeStateHelperInterface $authorizeStateHelper,
        private readonly SynchronizationHelperInterface $synchronizationHelper,
        private readonly Logger $logger,
        private readonly HppHelper $hppHelper,
    ) {
    }

    /**
     * {@inheritdoc}
     * @throws PaymentException|InconsistentCriteriaIdsException
     * @throws JsonException
     */
    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): RedirectResponse
    {
        /** @var SalesChannelContext|null $salesChannelContext */
        $salesChannelContext = $request->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        // Composable-Frontends
        if ($this->hppHelper->isHpp($request)) {
            return $this->hppHelper->handlePayment($request, $salesChannelContext);
        }

        $requestData = $this->fetchRequestData();

        $orderEntity = $this->getOrderFromTransaction($transaction->getOrderTransactionId(), $context);

        $order_request = $this->requestHydrator->hydrate($orderEntity?->getId(), $transaction, $requestData, $salesChannelContext);
        $response = $this->client->request($order_request, $context);

        if ($response->getHttpStatus() !== 200
            || strtolower($response->getResponse()['fraud_status']) === strtolower(AbstractKlarnaPaymentHandler::FRAUD_STATUS_REJECTED)
        ) {
            $errorMessage = $this->translator->trans('KlarnaPayment.errorMessages.paymentDeclined');

            throw PaymentException::asyncProcessInterrupted($transaction->getOrderTransactionId(), $errorMessage);
        }

        $this->saveTransactionData($this->getOrderTransactionById($transaction->getOrderTransactionId(), $context), $response,
            $salesChannelContext->getContext(), $requestData->get('klarnaAuthorizationToken'));

        $this->synchronizationHelper->syncBillingAddress($orderEntity, $transaction, $response->getResponse()['order_id'], $salesChannelContext);

        return new RedirectResponse($transaction->getReturnUrl());
    }

    /**
     * {@inheritdoc}
     * @throws PaymentException
     * @throws InconsistentCriteriaIdsException
     */
    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void
    {
        $orderTransaction = $this->getOrderTransactionById($transaction->getOrderTransactionId(), $context);
        $orderEntity = $this->orderFetcher->getOrderFromOrder($orderTransaction?->getOrderId(), $context);

        if (!$orderEntity || $orderEntity->getStateMachineState() === null) {
            $errorMessage = $this->translator->trans('KlarnaPayment.errorMessages.genericError');

            throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransactionId(), $errorMessage);
        }

        $addressRequest = $this->addressRequestHydrator->hydrate($orderEntity, $context);
        $orderRequest = $this->orderRequestHydrator->hydrate($orderEntity, $context);
        $customFields = $orderTransaction?->getCustomFields() ?? [];

        $update = [
            'id' => $orderEntity->getId(),
            OrderExtension::EXTENSION_NAME => [
                'orderAddressHash' => $this->updateAddressRequestHasher->getHash($addressRequest),
                'orderCartHash' => $this->updateOrderRequestHasher->getHash($orderRequest, self::CART_HASH_CURRENT_VERSION),
                'orderCartHashVersion' => self::CART_HASH_CURRENT_VERSION,
                'authorizationToken' => $customFields['klarna_authorization_token'],
            ],
        ];

        $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($update): void {
            $this->orderRepository->update([$update], $context);
        });

        if ($orderTransaction?->getCustomFields() === null || !array_key_exists(CustomFieldInstaller::FIELD_KLARNA_FRAUD_STATUS,
                $orderTransaction?->getCustomFields())) {
            return;
        }

        if (strtolower($orderTransaction?->getCustomFields()[CustomFieldInstaller::FIELD_KLARNA_FRAUD_STATUS]) === strtolower(AbstractKlarnaPaymentHandler::FRAUD_STATUS_ACCEPTED)) {
            if ($this->orderStatusValidator->isAutomaticCapture(
                null,
                $orderEntity->getStateMachineState()->getTechnicalName(),
                $orderEntity->getSalesChannelId(),
                $context
            )) {
                $this->captureStateHelper->processOrderCapture($orderEntity, $context);

                $this->logger->debug('Finalize order: auto capture order.', [
                    'transaction' => $transaction,
                    'orderEntity' => $orderEntity
                ]);

                return;
            }

            $this->authorizeStateHelper->processOrderAuthorize($orderEntity, $context);

            $this->logger->debug('Finalize order: authorize order.', [
                'transaction' => $transaction,
                'orderEntity' => $orderEntity
            ]);
        }
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return true;
    }
}
