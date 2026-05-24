<?php

/** @noinspection PhpMissingParentConstructorInspection */
/** @noinspection MagicMethodsValidityInspection */

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Storefront;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtension;
use KlarnaPayment\Components\Event\OrderCreatedThroughAuthorizationCallback;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CheckoutController;
use Shopware\Storefront\Controller\Exception\StorefrontRouteNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Monolog\Logger;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class KlarnaCheckoutController extends CheckoutController
{
    public function __construct(
        private readonly CheckoutController $inner,
        private readonly EntityRepository $orderRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityRepository $cartDataRepository,
        private readonly Logger $logger,
        private readonly ConfigReaderInterface $configReader,
    ) {
    }

    public function getDecorated(): CheckoutController
    {
        return $this->inner;
    }

    /**
     * @throws StorefrontRouteNotFoundException
     */
    public function order(RequestDataBag $data, SalesChannelContext $context, Request $request): Response
    {
        $authToken = $data->getString('klarnaAuthorizationToken');

        // If the auth token is empty, it's not a Klarna order
        if (empty($authToken)) {
            return $this->inner->order($data, $context, $request);
        }

        $config = $this->configReader->read($context->getSalesChannelId());


        if ($request->hasSession()) {
            try {
                $isExpress = (bool)$request->getSession()?->get(KlarnaExpressCheckoutController::KLARNA_EXPRESS_SESSION_KEY);
            } catch (SessionNotFoundException) {
                $isExpress = false;
            }
        }

        // if the auth-callback is deactivated or Klarna-Express, go on with the standard Shopware
        if ((isset($isExpress) && $isExpress === true) || !$config->get('kpUseAuthorizationCallback', false)) {
            return $this->inner->order($data, $context, $request);
        }

        // at this point the order should already have been created using the auth-callback
        $criteria = (new Criteria())
            ->addAssociation(OrderExtension::EXTENSION_NAME)
            ->addFilter(new EqualsFilter(OrderExtension::EXTENSION_NAME . '.authorizationToken', $authToken));

        $orderId = $this->orderRepository->searchIds($criteria, $context->getContext())->firstId();

        if (null === $orderId) {
            $this->addFlash('danger', $this->trans("KlarnaPayment.errorMessages.genericError"));

            $this->logger->error(
                "order created with the authorization-callback was not found", [
                "authorizationToken" => $authToken
            ]);

            return $this->forwardToRoute('frontend.checkout.confirm.page');
        }

        $this->logger->debug("order finished with the authorization-callback", ["orderId" => $orderId, "authorizationToken" => $authToken]);

        $this->eventDispatcher->dispatch(new OrderCreatedThroughAuthorizationCallback());

        return new RedirectResponse($this->generateUrl('frontend.checkout.finish.page', ['orderId' => $orderId]));
    }

    /**
     * @Route("/klarna/checkout/saveFormData", defaults={"csrf_protected": false, "XmlHttpRequest": true}, name="widgets.klarna.checkout.save", methods={"POST"})
     */
    #[Route(path: '/klarna/checkout/saveFormData', name: 'widgets.klarna.checkout.save', defaults: [
        'csrf_protected' => false,
        'XmlHttpRequest' => true
    ], methods: ['POST'])]
    public function saveFormData(RequestDataBag $dataBag, SalesChannelContext $context): Response
    {
        // Save inputs excluding tos and inputs containing klarna
        $params = array_filter($dataBag->all(), static function ($param) {
            return !(strpos((string)$param, 'klarna') !== false) && $param !== 'tos';
        }, ARRAY_FILTER_USE_KEY);

        $this->cartDataRepository->upsert([['cartToken' => $context->getToken(), 'payload' => $params]], $context->getContext());

        return new Response();
    }

    public function cartPage(Request $request, SalesChannelContext $context): Response
    {
        return $this->inner->cartPage($request, $context);
    }

    public function cartJson(Request $request, SalesChannelContext $context): Response
    {
        if (!method_exists($this->inner, 'cartJson')) {
            return new Response();
        }

        return $this->inner->cartJson($request, $context);
    }

    public function confirmPage(Request $request, SalesChannelContext $context): Response
    {
        return $this->inner->confirmPage($request, $context);
    }

    public function finishPage(Request $request, SalesChannelContext $context, ?RequestDataBag $dataBag = null): Response
    {
        /** @phpstan-ignore-next-line */
        return $this->inner->finishPage($request, $context, $dataBag);
    }

    public function info(Request $request, SalesChannelContext $context): Response
    {
        return $this->inner->info($request, $context);
    }

    public function offcanvas(Request $request, SalesChannelContext $context): Response
    {
        return $this->inner->offcanvas($request, $context);
    }
}
