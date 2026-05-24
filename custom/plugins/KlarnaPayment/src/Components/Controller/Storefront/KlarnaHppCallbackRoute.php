<?php declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Storefront;

use Exception;
use JsonException;
use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\GetHppSessionDetails\GetHppSessionDetailsRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\GetOrder\GetOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateMerchantReferences\UpdateMerchantReferencesRequestHydratorInterface;
use KlarnaPayment\Components\Client\Response\GenericResponse;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Components\Helper\StateHelper\Authorize\AuthorizeStateHelperInterface;
use KlarnaPayment\Components\Helper\StateHelper\Capture\CaptureStateHelperInterface;
use KlarnaPayment\Components\Helper\StateHelper\Cancel\CancelStateHelperInterface;
use KlarnaPayment\Components\Helper\StateHelper\Unconfirmed\UnconfirmedStateHelperInterface;
use KlarnaPayment\Components\PaymentHandler\AbstractKlarnaPaymentHandler;
use KlarnaPayment\Components\Validator\OrderTransitionChangeValidator;

use Monolog\Logger;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RequestStack;

#[Route(defaults: ['_entity' => 'order', '_routeScope' => ['store-api']])]
class KlarnaHppCallbackRoute extends AbstractKlarnaHppCallbackRoute
{
    public function __construct(
        private readonly OrderFetcherInterface $orderFetcher,
        protected readonly EntityRepository $transactionRepository,
        protected ClientInterface $client,
        protected GetHppSessionDetailsRequestHydratorInterface $getHppSessionDetails,
        protected GetOrderRequestHydratorInterface $getOrderRequest,
        private readonly OrderTransitionChangeValidator $orderStatusValidator,
        private readonly CaptureStateHelperInterface $captureStateHelper,
        protected CancelStateHelperInterface $cancelStateHelper,
        protected UnconfirmedStateHelperInterface $unconfirmedStateHelper,
        private readonly AuthorizeStateHelperInterface $authorizeStateHelper,
        private readonly UpdateMerchantReferencesRequestHydratorInterface $updateMerchantReferencesRequest,
        protected SalesChannelContextPersister $salesChannelContextPersister,
        protected RequestStack $requestStack,
        protected Logger $logger
    ) {

    }

    public function getDecorated(): AbstractKlarnaHppCallbackRoute
    {
        throw new DecorationPatternException(self::class);
    }

    public function load(Criteria $criteria, SalesChannelContext $context): StoreApiResponse
    {
        return new StoreApiResponse([]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/store-api/klarna/callback/hpp-back/{order_id}', name: 'store-api.klarna.callback.hpp-back', defaults: [
        '_entity' => 'order',
        'csrf_protected' => false,
        'XmlHttpRequest' => true
    ], methods: ['GET'])]
    public function hppBackCallback(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new RuntimeException("hpp-back: Request object not found");
        }

        $orderId = $request->get('order_id');

        $this->logger->notice('hpp-back', [
            'orderId' => $orderId,
            'request' => $request
        ]);

        $order = $this->orderFetcher->getOrderFromOrder($orderId, $salesChannelContext->getContext());

        $customFields = $order?->getCustomFields();

        $hppRedirectError = $customFields[CustomFieldInstaller::KLARNA_HPP_REDIRECT_ERROR];

        $this->unconfirmedStateHelper->processOrderUnconfirmation($order, $salesChannelContext->getContext());

        return new RedirectResponse($hppRedirectError);
    }

    #[Route(path: '/store-api/klarna/callback/hpp-cancel/{order_id}', name: 'store-api.klarna.callback.hpp-cancel', defaults: [
        '_entity' => 'order',
        'csrf_protected' => false,
        'XmlHttpRequest' => true
    ], methods: ['GET'])]
    public function hppCancelCallback(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new RuntimeException("hpp-cancel: Request object not found");
        }

        $orderId = $request->get('order_id');

        $this->logger->notice('hpp-cancel', [
            'orderId' => $orderId,
            'request' => $request
        ]);

        $order = $this->orderFetcher->getOrderFromOrder($orderId, $salesChannelContext->getContext());

        $customFields = $order?->getCustomFields();

        $hppRedirectError = $customFields[CustomFieldInstaller::KLARNA_HPP_REDIRECT_ERROR];

        $this->unconfirmedStateHelper->processOrderUnconfirmation($order, $salesChannelContext->getContext());

        return new RedirectResponse($hppRedirectError);
    }

    #[Route(path: '/store-api/klarna/callback/hpp-error/{order_id}', name: 'store-api.klarna.callback.hpp-error', defaults: [
        '_entity' => 'order',
        'csrf_protected' => false,
        'XmlHttpRequest' => true
    ], methods: ['GET'])]
    public function hppErrorCallback(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new RuntimeException("hpp-error: Request object not found");
        }

        $orderId = $request->get('order_id');

        $this->logger->notice('hpp-error', [
            'orderId' => $orderId,
            'request' => $request
        ]);

        $order = $this->orderFetcher->getOrderFromOrder($orderId, $salesChannelContext->getContext());

        $customFields = $order?->getCustomFields();

        $hppRedirectError = $customFields[CustomFieldInstaller::KLARNA_HPP_REDIRECT_ERROR];

        $this->cancelStateHelper->processOrderCancellation($order, $salesChannelContext->getContext());

        return new RedirectResponse($hppRedirectError);
    }

    #[Route(path: '/store-api/klarna/callback/hpp-failure/{order_id}', name: 'store-api.klarna.callback.hpp-failure', defaults: [
        '_entity' => 'order',
        'csrf_protected' => false,
        'XmlHttpRequest' => true
    ], methods: ['GET'])]
    public function hppFailureCallback(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new RuntimeException("hpp-failure: Request object not found");
        }

        $orderId = $request->get('order_id');

        $this->logger->notice('hpp-failure', [
            'orderId' => $orderId,
            'request' => $request
        ]);

        $order = $this->orderFetcher->getOrderFromOrder($orderId, $salesChannelContext->getContext());

        $customFields = $order?->getCustomFields();

        $hppRedirectError = $customFields[CustomFieldInstaller::KLARNA_HPP_REDIRECT_ERROR];

        $this->cancelStateHelper->processOrderCancellation($order, $salesChannelContext->getContext());

        return new RedirectResponse($hppRedirectError);
    }

    /**
     * @throws JsonException
     */
    #[Route(path: '/store-api/klarna/callback/hpp-success/{order_id}', name: 'store-api.klarna.callback.hpp-success', defaults: [
        '_entity' => 'order',
        'csrf_protected' => false,
        'XmlHttpRequest' => true
    ], methods: ['GET'])]
    public function hppSuccessCallback(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new RuntimeException("hpp-success: Request object not found");
        }

        $orderId = $request->get('order_id');

        $this->logger->notice('hpp-success', [
            'orderId' => $orderId,
            'request' => $request
        ]);

        $context = $salesChannelContext->getContext();

        $order = $this->orderFetcher->getOrderFromOrder($orderId, $context);

        $customFields = $order?->getCustomFields();

        $hppSessionId = $customFields[CustomFieldInstaller::KLARNA_HPP_SESSION_ID];
        $hppRedirectSuccess = $customFields[CustomFieldInstaller::KLARNA_HPP_REDIRECT_SUCCESS];
        $hppRedirectError = $customFields[CustomFieldInstaller::KLARNA_HPP_REDIRECT_ERROR];
        $sessionToken = $customFields[CustomFieldInstaller::KLARNA_HPP_SESSION_TOKEN];

        $countryIso = $order?->getDeliveries()?->first()?->getShippingOrderAddress()?->getCountry()?->getIso();

        if (empty($countryIso)) {
            $countryIso = $order?->getBillingAddress()?->getCountry()?->getIso();
        }

        $hppSessionDetailsRequest = $this->getHppSessionDetails->hydrate($hppSessionId, $salesChannelContext, $countryIso);
        $response = $this->client->request($hppSessionDetailsRequest, $context);

        if (!$this->isValidResponseStatus($response) || !isset($response->getResponse()['order_id'])) {
            return new RedirectResponse($hppRedirectError);
        }

        $data = $response->getResponse();

        $dataBag = new RequestDataBag();
        $dataBag->add([
            'order_id' => $order?->getId(),
            'klarna_order_id' => $data['order_id'],
            'salesChannel' => $salesChannelContext->getSalesChannel()->getId(),
        ]);

        $klarnaOrderRequest = $this->getOrderRequest->hydrate($dataBag);

        $response = $this->client->request($klarnaOrderRequest, $context);

        if (!$this->isValidResponseStatus($response)) {
            return new RedirectResponse($hppRedirectError);
        }

        $transaction = $order?->getTransactions()?->last();

        $this->saveTransactionData($transaction, $response, $context);

        $updateMerchantReferencesRequest = $this->updateMerchantReferencesRequest->hydrate($order, $context, $data['order_id']);

        $this->client->request($updateMerchantReferencesRequest, $context);

        $this->updatePaymentStates($order?->getId(), $salesChannelContext);

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $customerId = $salesChannelContext->getCustomer()?->getId();

        $payload = $this->salesChannelContextPersister->load($sessionToken, $salesChannelId, $customerId);

        // Since SalesChannelContextPersister::save uses `array_replace_recursive`, we set the session to null rather than unsetting it.
        $payload["klarnaHppSession"] = null;
        // Update customer's context without the Klarna HPP session-data.
        $this->salesChannelContextPersister->save($sessionToken, $payload, $salesChannelId);

        return new RedirectResponse($hppRedirectSuccess);
    }

    protected function saveTransactionData(OrderTransactionEntity $transaction, GenericResponse $response, Context $context): void
    {
        $customFields = $transaction->getCustomFields() ?? [];

        $customFields = array_merge($customFields, [
            CustomFieldInstaller::FIELD_KLARNA_ORDER_ID => $response->getResponse()['order_id'],
            CustomFieldInstaller::FIELD_KLARNA_FRAUD_STATUS => $response->getResponse()['fraud_status'],
        ]);

        $update = [
            'id' => $transaction->getId(),
            'customFields' => $customFields,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($update): void {
            $this->transactionRepository->update([$update], $context);
        });
    }

    private function updatePaymentStates(string $orderId, SalesChannelContext $salesChannelContext): void
    {
        $order = $this->orderFetcher->getOrderFromOrder($orderId, $salesChannelContext->getContext());

        $transaction = $order?->getTransactions()?->last();

        if ($transaction?->getCustomFields() === null || !array_key_exists(CustomFieldInstaller::FIELD_KLARNA_FRAUD_STATUS, $transaction?->getCustomFields())) {
            return;
        }

        if (strtolower($transaction->getCustomFields()[CustomFieldInstaller::FIELD_KLARNA_FRAUD_STATUS]) === strtolower(AbstractKlarnaPaymentHandler::FRAUD_STATUS_ACCEPTED)) {
            if ($this->orderStatusValidator->isAutomaticCapture(
                null,
                $order->getStateMachineState()->getTechnicalName(),
                $salesChannelContext->getSalesChannel()->getId(),
                $salesChannelContext->getContext()
            )) {

                $this->captureStateHelper->processOrderCapture($order, $salesChannelContext->getContext());
                return;
            }

            $this->authorizeStateHelper->processOrderAuthorize($order, $salesChannelContext->getContext());
        }
    }

    private function isValidResponseStatus(GenericResponse $response): bool
    {
        return in_array($response->getHttpStatus(), [Response::HTTP_OK, Response::HTTP_NO_CONTENT], true);
    }
}