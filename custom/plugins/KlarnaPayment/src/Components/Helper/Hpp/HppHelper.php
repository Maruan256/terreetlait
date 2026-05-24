<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\Hpp;

use JsonException;
use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\CreateHppSession\CreateHppSessionRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\CreateSession\CreateExtendedSessionRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession\UpdateExtendedSessionRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession\UpdateSessionRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\Address\AddressStructHydratorInterface;
use KlarnaPayment\Components\Client\Response\GenericResponse;
use KlarnaPayment\Components\Converter\CustomOrderConverter;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use LogicException;
use Monolog\Logger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class HppHelper implements HppHelperInterface
{
    public function __construct(
        private CreateExtendedSessionRequestHydratorInterface $createSessionHydrator,
        private ClientInterface $client,
        private SalesChannelContextPersister $salesChannelContextPersister,
        private AddressStructHydratorInterface $addressHydrator,
        private EntityRepository $orderRepository,
        private CustomOrderConverter $orderConverter,
        private OrderFetcherInterface $orderFetcher,
        private UpdateExtendedSessionRequestHydratorInterface $updateSessionHydrator,
        private CreateHppSessionRequestHydratorInterface $requestHydrator,
        private Logger $logger,
        private string $appSecret
    ) {
    }

    /**
     * @throws JsonException
     */
    public function handlePayment(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $this->logger->info('klarna-composable-frontend handle-payment', [
            'routeScope' => $request->attributes->get('_routeScope'),
            'request' => $request
        ]);

        $token = $salesChannelContext->getToken();
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $customerId = $salesChannelContext->getCustomer()?->getId();

        $errorUrl = $request->get('errorUrl');
        $finishUrl = $request->get('finishUrl');

        $data = $this->salesChannelContextPersister->load($token, $salesChannelId, $customerId);

        if (!isset($data[CustomFieldInstaller::KLARNA_SESSION_KEY])) {
            return new RedirectResponse($errorUrl);
        }

        $orderId = $request->get('orderId');
        $order = $this->orderFetcher->getOrderFromOrder($orderId, $salesChannelContext->getContext());

        if (!$order) {
            $this->logger->error('klarna-composable-frontend handle-payment: Order not found by id', [
                'orderId' => $orderId
            ]);

            return new RedirectResponse($errorUrl);
        }

        $cart = $this->orderConverter->convertOrderToCart($order, $salesChannelContext->getContext());
        $sessionId = $data[CustomFieldInstaller::KLARNA_SESSION_KEY][UpdateSessionRequestHydratorInterface::KLARNA_SESSION_ID];

        $klarnaSessionUpdateRequest = $this->updateSessionHydrator->hydrate($sessionId, $cart, $salesChannelContext);
        $updateSessionResponse = $this->client->request($klarnaSessionUpdateRequest, $salesChannelContext->getContext());

        if ($updateSessionResponse->getHttpStatus() === Response::HTTP_NOT_FOUND) {
            $this->logger->warning('klarna-composable-frontend handle-payment: Session ID not found, creating new one', [
                'oldSessionId' => $sessionId
            ]);

            $createSessionResponse = $this->createKlarnaSession($cart, $salesChannelContext);

            if ($this->isValidResponseStatus($createSessionResponse)) {
                $this->addKlarnaSessionToShopware($createSessionResponse->getResponse(), $salesChannelContext);
                $data = $this->salesChannelContextPersister->load($token, $salesChannelId, $customerId);
            } else {
                $this->logger->error('klarna-composable-frontend handle-payment: Create session failed', [
                    'createSessionRequest' => $createSessionResponse->jsonSerialize()
                ]);
            }
        }

        $hppSessionRequest = $this->requestHydrator->hydrate($data[CustomFieldInstaller::KLARNA_SESSION_KEY], $request, $salesChannelContext);

        $response = $this->client->request($hppSessionRequest, $salesChannelContext->getContext());

        if (!$this->isValidResponseStatus($response)) {
            return new RedirectResponse($errorUrl);
        }

        $customFields = $order->getCustomFields() ?? [];
        $customFields[CustomFieldInstaller::KLARNA_HPP_SESSION_ID] = $response->getResponse()['session_id'];
        $customFields[CustomFieldInstaller::KLARNA_HPP_REDIRECT_SUCCESS] = $finishUrl;
        $customFields[CustomFieldInstaller::KLARNA_HPP_REDIRECT_ERROR] = $errorUrl;
        $customFields[CustomFieldInstaller::KLARNA_HPP_SESSION_TOKEN] = $salesChannelContext->getToken();

        $this->orderRepository->update([
            [
                'id' => $orderId,
                'customFields' => $customFields
            ]
        ], $salesChannelContext->getContext());

        return new RedirectResponse($response->getResponse()['redirect_url']);
    }

    public function isHpp(Request $request): bool
    {
        $routeScope = $request->attributes->get('_routeScope', "");
        $routeScope = is_array($routeScope) ? $routeScope[0] ?? "" : $routeScope;

        return $routeScope === "store-api" ;
    }

    private function isValidResponseStatus(GenericResponse $response): bool
    {
        return in_array($response->getHttpStatus(), [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_NO_CONTENT], true);
    }

    private function createKlarnaSession(Cart $cart, SalesChannelContext $salesChannelContext): GenericResponse
    {
        $request = $this->createSessionHydrator->hydrate($cart, $salesChannelContext);

        return $this->client->request($request, $salesChannelContext->getContext());
    }

    /**
     * @throws JsonException
     */
    private function addKlarnaSessionToShopware(array $klarnaSession, SalesChannelContext $salesChannelContext): void
    {
        $token = $salesChannelContext->getToken();
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $customerId = $salesChannelContext->getCustomer()?->getId();

        $payload = $this->loadPayloadFromSalesChannelApiContext($salesChannelContext);

        $payload[CustomFieldInstaller::KLARNA_SESSION_KEY] = [
            UpdateSessionRequestHydratorInterface::KLARNA_SESSION_ID => $klarnaSession['session_id'],
            UpdateSessionRequestHydratorInterface::KLARNA_CLIENT_TOKEN => $klarnaSession['client_token'],
            UpdateSessionRequestHydratorInterface::KLARNA_PAYMENT_METHOD_CATEGORIES => $klarnaSession['payment_method_categories'],
            UpdateSessionRequestHydratorInterface::KLARNA_ADDRESS_HASH => $this->getAddressHash($salesChannelContext)
        ];

        $this->salesChannelContextPersister->save($token, $payload, $salesChannelId, $customerId);
    }

    /**
     * @throws JsonException
     */
    private function loadPayloadFromSalesChannelApiContext(SalesChannelContext $salesChannelContext): array
    {
        $token = $salesChannelContext->getToken();
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $customerId = $salesChannelContext->getCustomer()?->getId();

        return $this->salesChannelContextPersister->load($token, $salesChannelId, $customerId);
    }

    /**
     * @throws JsonException
     */
    private function getAddressHash(SalesChannelContext $salesChannelContext): ?string
    {
        $customer = $this->addressHydrator->hydrateFromContext($salesChannelContext);

        if ($customer === null) {
            return null;
        }

        $json = json_encode($customer, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        if (empty($json)) {
            throw new LogicException('could not generate hash');
        }

        if (empty($this->appSecret)) {
            throw new LogicException('empty app secret');
        }

        return hash_hmac('sha256', $json, $this->appSecret);
    }
}