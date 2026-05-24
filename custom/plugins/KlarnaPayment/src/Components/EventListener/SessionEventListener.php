<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use JsonException;
use KlarnaPayment\Components\CartHasher\CartHasherInterface;
use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\CreateSession\CreateSessionRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession\UpdateSessionRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\Address\AddressStructHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\Customer\CustomerStructHydratorInterface;
use KlarnaPayment\Components\Client\Response\GenericResponse;
use KlarnaPayment\Components\Client\Struct\Attachment;
use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Controller\Storefront\KlarnaExpressCheckoutController;
use KlarnaPayment\Components\Converter\CustomOrderConverter;
use KlarnaPayment\Components\Event\OrderCreatedThroughAuthorizationCallback;
use KlarnaPayment\Components\Extension\ErrorMessageExtension;
use KlarnaPayment\Components\Extension\SessionDataExtension;
use KlarnaPayment\Components\Factory\MerchantDataFactoryInterface;
use KlarnaPayment\Components\Helper\MediaHelper\MediaHelper;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use LogicException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Event\SwitchContextEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\RouteRequest\SetPaymentOrderRouteRequestEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

readonly class SessionEventListener implements EventSubscriberInterface
{
    public function __construct(
        private PaymentHelperInterface $paymentHelper,
        private CreateSessionRequestHydratorInterface $requestHydrator,
        private UpdateSessionRequestHydratorInterface $requestUpdateHydrator,
        private AddressStructHydratorInterface $addressHydrator,
        private CustomerStructHydratorInterface $customerHydrator,
        private ClientInterface $client,
        private CartHasherInterface $cartHasher,
        private MerchantDataFactoryInterface $merchantDataFactory,
        private CustomOrderConverter $orderConverter,
        private OrderFetcherInterface $orderFetcher,
        private SystemConfigService $systemConfigService,
        private ConfigReaderInterface $configReader,
        private RequestStack $requestStack,
        private string $appSecret
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'startKlarnaSession',
            AccountEditOrderPageLoadedEvent::class => 'startKlarnaSession',
            CheckoutOrderPlacedEvent::class => 'resetKlarnaSession',
            CustomerLoginEvent::class => 'resetKlarnaSession',
            SetPaymentOrderRouteRequestEvent::class => 'resetKlarnaSession',
            OrderCreatedThroughAuthorizationCallback::class => 'resetKlarnaSession',
            SwitchContextEvent::CONSISTENT_CHECK => 'storePaymentCategory'
        ];
    }

    /**
     * @throws JsonException
     */
    public function startKlarnaSession(PageLoadedEvent $event): void
    {
        if (!$this->getSession()) {
            return;
        }

        $context = $event->getSalesChannelContext();

        if (!$this->paymentHelper->isKlarnaPaymentsEnabled($context)) {
            return;
        }

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $cart = $event->getPage()->getCart();
        } elseif ($event instanceof AccountEditOrderPageLoadedEvent) {
            $cart = $this->convertCartFromOrder($event->getPage()->getOrder(), $event->getContext());
        } else {
            return;
        }

        $response = $this->getSession()?->has(KlarnaExpressCheckoutController::KLARNA_EXPRESS_SESSION_KEY)
            ? (new GenericResponse())->assign(['httpStatus' => Response::HTTP_BAD_REQUEST])
            : $this->createOrUpdateKlarnaSession($event, $cart, $context);

        if ($this->isValidResponseStatus($response) && !$this->hasValidKlarnaSession($context)) {
            $this->addKlarnaSessionToShopwareSession($response->getResponse(), $context);
        }

        $this->createSessionDataExtension($response, $event->getPage(), $cart, $context);
        $this->createPaymentCategories($event->getPage());
        $this->filterKlarnaPaymentMethods($event->getPage());

        $this->updateGlobalPurchaseFlowSystemConfig($event->getPage(), $context);
    }

    public function resetKlarnaSession(): void
    {
        $this->getSession()?->remove(UpdateSessionRequestHydratorInterface::KLARNA_SESSION_ID);
        $this->getSession()?->remove(UpdateSessionRequestHydratorInterface::KLARNA_CLIENT_TOKEN);
        $this->getSession()?->remove(UpdateSessionRequestHydratorInterface::KLARNA_PAYMENT_METHOD_CATEGORIES);
        $this->getSession()?->remove(UpdateSessionRequestHydratorInterface::KLARNA_ADDRESS_HASH);
        $this->getSession()?->remove(UpdateSessionRequestHydratorInterface::KLARNA_SELECTED_CATEGORY);
    }

    public function storePaymentCategory(SwitchContextEvent $switchContextEvent): void
    {
        if ($identifier = $switchContextEvent->getRequestData()->getString(UpdateSessionRequestHydratorInterface::KLARNA_SELECTED_CATEGORY)) {
            $this->getSession()?->set(UpdateSessionRequestHydratorInterface::KLARNA_SELECTED_CATEGORY, $identifier);
        } else {
            $this->getSession()?->set(UpdateSessionRequestHydratorInterface::KLARNA_SELECTED_CATEGORY, "");
        }
    }

    private function createErrorMessageExtension(PageLoadedEvent $event): void
    {
        $errorMessageExtension = new ErrorMessageExtension(ErrorMessageExtension::GENERIC_ERROR);

        $event->getPage()->addExtension(ErrorMessageExtension::EXTENSION_NAME, $errorMessageExtension);
    }

    /**
     * @throws JsonException
     */
    private function createSessionDataExtension(GenericResponse $response, Struct $page, Cart $cart, SalesChannelContext $context): void
    {
        if (!($page instanceof Page)) {
            return;
        }

        $config = $this->configReader->read($context->getSalesChannel()->getId());

        $sessionData = new SessionDataExtension();
        $sessionData->assign([
            'selectedPaymentMethodCategory' => $this->getSelectedPaymentCategory(),
            'cartHash' => $this->cartHasher->generate($cart, $context),
            'useAuthorizationCallback' => $config->get('kpUseAuthorizationCallback'),
        ]);

        if ($this->hasValidKlarnaSession($context)) {
            $sessionData->assign([
                'sessionId' => $this->getSession()?->get(UpdateSessionRequestHydratorInterface::KLARNA_SESSION_ID),
                'clientToken' => $this->getSession()?->get(UpdateSessionRequestHydratorInterface::KLARNA_CLIENT_TOKEN),
                'paymentMethodCategories' => $this->getSession()?->get(UpdateSessionRequestHydratorInterface::KLARNA_PAYMENT_METHOD_CATEGORIES),
            ]);
        } elseif ($this->getSession()?->has(KlarnaExpressCheckoutController::KLARNA_EXPRESS_SESSION_KEY)) {
            $sessionData->assign([
                'isKlarnaExpress' => true,
                'clientToken' => $this->getSession()?->get(UpdateSessionRequestHydratorInterface::KLARNA_CLIENT_TOKEN),
            ]);
        } elseif ($this->isValidResponseStatus($response)) {
            $sessionData->assign([
                'sessionId' => $response->getResponse()['session_id'],
                'clientToken' => $response->getResponse()['client_token'],
                'paymentMethodCategories' => $response->getResponse()['payment_method_categories'],
            ]);
        }

        if ($this->paymentHelper->isKlarnaPaymentsSelected($context)) {
            $extraMerchantData = $this->merchantDataFactory->getExtraMerchantData($sessionData, $cart, $context);

            if (!empty($extraMerchantData->getAttachment())) {
                $attachment = new Attachment();
                $attachment->assign([
                    'data' => $extraMerchantData->getAttachment(),
                ]);
            } else {
                $attachment = null;
            }

            $sessionData->assign([
                'customerData' => [
                    'billing_address' => $this->addressHydrator->hydrateFromContext($context),
                    'shipping_address' => $this->addressHydrator->hydrateFromContext($context, AddressStructHydratorInterface::TYPE_SHIPPING),
                    'customer' => $this->customerHydrator->hydrate($context),
                    'merchant_data' => $extraMerchantData->getMerchantData(),
                    'attachment' => $attachment,
                ],
            ]);
        }

        $page->addExtension(SessionDataExtension::EXTENSION_NAME, $sessionData);
    }

    private function createPaymentCategories(Page $page): void
    {
        if (!method_exists($page, 'getPaymentMethods')) {
            return;
        }

        /** @var null|SessionDataExtension $sessionData */
        $sessionData = $page->getExtension(SessionDataExtension::EXTENSION_NAME);
        /** @var null|PaymentMethodEntity $original */
        $original = $page->getPaymentMethods()->get(PaymentMethodInstaller::KLARNA_PAY);

        if (is_null($sessionData) || is_null($original)) {
            return;
        }

        foreach ($sessionData->getPaymentMethodCategories() as $paymentCategory) {
            $paymentMethod = clone $original;

            $badgeUrl = $paymentCategory["asset_urls"]["standard"] ?? "";

            // we do this mainly for compatibility reasons
            // the frontend twig could be overwritten
            if(!empty($badgeUrl)) {
                $paymentMedia = new MediaEntity();
                $badgePath = parse_url($badgeUrl, PHP_URL_PATH);

                $paymentMedia->setId(Uuid::randomHex());
                $paymentMedia->setUrl($badgeUrl);
                $paymentMedia->setMimeType('image/svg+xml');
                $paymentMedia->setFileExtension('svg');
                $paymentMedia->setFileName(pathinfo($badgePath, PATHINFO_FILENAME));

                $paymentMethod->setMedia($paymentMedia);
            }

            $paymentMethod->setId($paymentCategory['identifier']);
            $paymentMethod->addExtension("klarna", new ArrayStruct([
                    "paymentId" => $original->getId(),
                    "identifier" => $paymentCategory['identifier']
                ])
            );
            $paymentMethod->setName($paymentCategory['name']);
            $paymentMethod->setDistinguishableName($paymentCategory['name']);
            $paymentMethod->setTranslated(
                array_merge(
                    $paymentMethod->getTranslated(),
                    ['name' => $paymentCategory['name'], 'distinguishableName' => $paymentCategory['name']]
                )
            );

            $page->getPaymentMethods()->add($paymentMethod);
        }
    }

    private function filterKlarnaPaymentMethods(Struct $page): void
    {
        if (!($page instanceof Page)) {
            return;
        }

        if (!method_exists($page, 'setPaymentMethods') || !method_exists($page, 'getPaymentMethods')) {
            return;
        }

        $isKlarnaExpress = $this->getSession()?->has(KlarnaExpressCheckoutController::KLARNA_EXPRESS_SESSION_KEY);

        $page->setPaymentMethods(
            $page->getPaymentMethods()->filter(
                static function (PaymentMethodEntity $paymentMethod) use ($isKlarnaExpress) {
                    // If deprecated payment method, remove
                    if (in_array($paymentMethod->getId(), PaymentMethodInstaller::KLARNA_DEPRECATED_IDS)) {
                        return false;
                    }

                    // Remove express if the current session is not Klarna-Express
                    if (!$isKlarnaExpress && array_key_exists($paymentMethod->getId(), PaymentMethodInstaller::KLARNA_EXPRESS_CHECKOUT_CODES)) {
                        return false;
                    }

                    /**
                     * Remove the main payment method because, at the checkout,
                     * we're going to use aliases with the same id.
                     **/
                    return $paymentMethod->getId() !== PaymentMethodInstaller::KLARNA_PAY;
                }
            )
        );
    }

    private function removeAllKlarnaPaymentMethods(Struct $page): void
    {
        if (!($page instanceof Page) || !method_exists($page, 'setPaymentMethods') || !method_exists($page, 'getPaymentMethods')) {
            return;
        }

        $page->setPaymentMethods(
            $page->getPaymentMethods()->filter(
                static function (PaymentMethodEntity $paymentMethod) {
                    return $paymentMethod->getId() !== PaymentMethodInstaller::KLARNA_PAY;
                }
            )
        );
    }

    /**
     * @throws JsonException
     */
    private function createOrUpdateKlarnaSession(PageLoadedEvent $event, Cart $cart, SalesChannelContext $context): GenericResponse
    {
        $response = $this->hasValidKlarnaSession($context)
            ? $this->updateKlarnaSession($this->getSession()?->get(UpdateSessionRequestHydratorInterface::KLARNA_SESSION_ID), $cart, $context)
            : $this->createKlarnaSession($cart, $context);

        if ($this->isValidResponseStatus($response)) {
            return $response;
        }

        if ($this->paymentHelper->isKlarnaPaymentsSelected($context)) {
            $this->createErrorMessageExtension($event);
        }

        $this->removeAllKlarnaPaymentMethods($event->getPage());

        return $response;
    }

    private function isValidResponseStatus(GenericResponse $response): bool
    {
        return in_array($response->getHttpStatus(), [Response::HTTP_OK, Response::HTTP_NO_CONTENT], true);
    }

    private function createKlarnaSession(Cart $cart, SalesChannelContext $context): GenericResponse
    {
        $request = $this->requestHydrator->hydrate($cart, $context);

        return $this->client->request($request, $context->getContext());
    }

    private function updateKlarnaSession(string $sessionId, Cart $cart, SalesChannelContext $context): GenericResponse
    {
        $request = $this->requestUpdateHydrator->hydrate($sessionId, $cart, $context);

        return $this->client->request($request, $context->getContext());
    }

    private function getSelectedPaymentCategory(): string
    {
        return $this->getSession()?->get(UpdateSessionRequestHydratorInterface::KLARNA_SELECTED_CATEGORY) ?? $this->getSession()?->get("") ?? "";
    }

    private function convertCartFromOrder(OrderEntity $orderEntity, Context $context): Cart
    {
        $order = $this->orderFetcher->getOrderFromOrder($orderEntity->getId(), $context);

        if ($order === null) {
            throw new LogicException('could not find order via id');
        }

        return $this->orderConverter->convertOrderToCart($order, $context);
    }

    /**
     * @param array<string,mixed> $klarnaSession
     * @throws JsonException
     */
    private function addKlarnaSessionToShopwareSession(array $klarnaSession, SalesChannelContext $context): void
    {
        $this->getSession()?->set(UpdateSessionRequestHydratorInterface::KLARNA_SESSION_ID, $klarnaSession['session_id']);
        $this->getSession()?->set(UpdateSessionRequestHydratorInterface::KLARNA_CLIENT_TOKEN, $klarnaSession['client_token']);
        $this->getSession()?->set(UpdateSessionRequestHydratorInterface::KLARNA_PAYMENT_METHOD_CATEGORIES, $klarnaSession['payment_method_categories']);
        $this->getSession()?->set(UpdateSessionRequestHydratorInterface::KLARNA_ADDRESS_HASH, $this->getAddressHash($context));
    }

    /**
     * @throws JsonException
     */
    private function hasValidKlarnaSession(SalesChannelContext $context): bool
    {
        return $this->getSession()?->has(UpdateSessionRequestHydratorInterface::KLARNA_SESSION_ID)
            && $this->getSession()?->has(UpdateSessionRequestHydratorInterface::KLARNA_CLIENT_TOKEN)
            && $this->getSession()?->has(UpdateSessionRequestHydratorInterface::KLARNA_PAYMENT_METHOD_CATEGORIES)
            && $this->getSession()?->has(UpdateSessionRequestHydratorInterface::KLARNA_ADDRESS_HASH) && $this->getSession()?->get(UpdateSessionRequestHydratorInterface::KLARNA_ADDRESS_HASH) === $this->getAddressHash($context);
    }

    /**
     * @throws JsonException
     */
    private function getAddressHash(SalesChannelContext $context): ?string
    {
        $customer = $this->addressHydrator->hydrateFromContext($context);

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

    private function getSession(): ?SessionInterface
    {
        /**
         * Check if we're in HTTP request context before accessing session
         * In CLI/cronjob/message queue context, there is no request and thus no session
         */
        if (!$this->requestStack->getMainRequest()) {
            return null;
        }

        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }
    }

    private function updateGlobalPurchaseFlowSystemConfig(Struct $page, SalesChannelContext $context): void
    {
        if (!($page instanceof Page)) {
            return;
        }

        if (!method_exists($page, 'getPaymentMethods')) {
            return;
        }

        $paymentMethodIds = $page->getPaymentMethods()->getIds();

        if (!$this->hasKlarnaPayment($paymentMethodIds)) {
            return;
        }

        $configRelatedSalesChannelId = $context->getSalesChannel()->getId();

        $currentConfig = $this->configReader->read($configRelatedSalesChannelId, false)
            ->get(ConfigReaderInterface::CONFIG_ACTIVE_GLOBALPURCHASEFLOW, null);

        if ($currentConfig === null) {
            $currentConfig = $this->configReader->read()->get(ConfigReaderInterface::CONFIG_ACTIVE_GLOBALPURCHASEFLOW);
            $configRelatedSalesChannelId = null;
        }

        $newConfig = in_array(PaymentMethodInstaller::KLARNA_PAY, $paymentMethodIds, true);

        if ($currentConfig === $newConfig) {
            return;
        }

        $settingKey = sprintf('%s%s', ConfigReaderInterface::SYSTEM_CONFIG_DOMAIN, ConfigReaderInterface::CONFIG_ACTIVE_GLOBALPURCHASEFLOW);

        $this->systemConfigService->set($settingKey, $newConfig, $configRelatedSalesChannelId);
    }

    private function hasKlarnaPayment(array $paymentMethodIds): bool
    {
        return in_array(PaymentMethodInstaller::KLARNA_PAY, $paymentMethodIds, true);
    }
}
