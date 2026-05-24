<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Storefront;

use KlarnaPayment\Components\Client\Hydrator\Request\CreateSession\CreateSessionRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession\UpdateSessionRequestHydratorInterface;
use KlarnaPayment\Components\Converter\CustomerAddressConverter;
use Monolog\Logger;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @RouteScope(scopes={"storefront"})
 * @Route(defaults={"_routeScope": {"storefront"}})
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class KlarnaExpressCheckoutController extends StorefrontController
{
    public const KLARNA_EXPRESS_SESSION_KEY = 'klarnaExpressCheckout';

    /** @var AbstractRegisterRoute */
    private $registerRoute;

    /** @var CartService */
    private $cartService;

    /** @var CustomerAddressConverter */
    private $customerAddressConverter;

    /** @var CreateSessionRequestHydratorInterface */
    private $createSessionRequestHydrator;

    /** @var RouterInterface */
    private $router;

    /** @var null|SessionInterface */
    private $session;

    /** @var RequestStack */
    private $requestStack;

    /** @var Logger */
    private $logger;

    public function __construct(
        AbstractRegisterRoute $registerRoute,
        CartService $cartService,
        CustomerAddressConverter $customerAddressConverter,
        CreateSessionRequestHydratorInterface $createSessionRequestHydrator,
        RouterInterface $router,
        ?SessionInterface $session,
        RequestStack $requestStack,
        Logger $logger
    ) {
        $this->registerRoute                = $registerRoute;
        $this->cartService                  = $cartService;
        $this->customerAddressConverter     = $customerAddressConverter;
        $this->createSessionRequestHydrator = $createSessionRequestHydrator;
        $this->router                       = $router;
        $this->session                      = $session;
        $this->requestStack                 = $requestStack;
        $this->logger                       = $logger;
    }

    /**
     * @Route("/klarna/checkout/session", defaults={"XmlHttpRequest": true}, name="widgets.klarna.checkout.session", methods={"GET"})
     */
    #[Route(path: '/klarna/checkout/session', name: 'widgets.klarna.checkout.session', methods: ['GET'], defaults: ['XmlHttpRequest' => true])]
    public function getSessionData(SalesChannelContext $context): JsonResponse
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        $createSessionRequest = $this->createSessionRequestHydrator->hydrate($cart, $context);

        return new JsonResponse($createSessionRequest->jsonSerialize());
    }

    /**
     * @Route("/klarna/checkout/login", defaults={"csrf_protected": false, "XmlHttpRequest": true}, name="widgets.klarna.checkout.login", methods={"POST"})
     */
    #[Route(path: '/klarna/checkout/login', name: 'widgets.klarna.checkout.login', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function login(Request $request, SalesChannelContext $context): JsonResponse
    {
        try {
            $this->getSession()->set(self::KLARNA_EXPRESS_SESSION_KEY, true);

            $this->registerRoute->register(
                $this->getCustomerRegisterData($request->get('collectedShippingAddress'), $context),
                $context,
                false
            );

            $this->getSession()->set(UpdateSessionRequestHydratorInterface::KLARNA_CLIENT_TOKEN, $request->get(UpdateSessionRequestHydratorInterface::KLARNA_CLIENT_TOKEN));
        } catch (\Exception $e) {
            $this->logger->error('Could not login customer for Klarna Express Checkout.', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return new JsonResponse(['success' => false], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['success' => true, 'redirectUrl' => $this->router->generate(
            'frontend.checkout.confirm.page',
            [],
            RouterInterface::ABSOLUTE_URL
        )]);
    }

    private function getCustomerRegisterData(array $klarnaAddress, SalesChannelContext $context): RequestDataBag
    {
        $customerAddressDataBag = $this->customerAddressConverter->convertToRegisterRequestDataBag($klarnaAddress, $context);

        return new RequestDataBag([
            'email'                 => $klarnaAddress['email'],
            'salutationId'          => $customerAddressDataBag->get('salutationId'),
            'firstName'             => $customerAddressDataBag->get('firstName'),
            'lastName'              => $customerAddressDataBag->get('lastName'),
            'billingAddress'        => $customerAddressDataBag,
            'guest'                 => true,
            'createCustomerAccount' => false,
            'acceptedDataProtection' => true
        ]);
    }

    // TODO: Remove me if compatibility is at least 6.4.2.0
    private function getSession(): SessionInterface
    {
        /** @phpstan-ignore-next-line */
        return $this->session ?? $this->requestStack->getSession();
    }
}
