<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Storefront;

use KlarnaPayment\Components\Callback\AuthorizationCallback;
use KlarnaPayment\Components\Callback\NotificationCallback;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Monolog\Logger;
use Throwable;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class KlarnaPaymentsCallbackController extends StorefrontController
{
    public function __construct(
        private readonly NotificationCallback $notificationCallback,
        private readonly AuthorizationCallback $authorizationCallback,
        private readonly SalesChannelContextPersister $contextPersister,
        private readonly AbstractSalesChannelContextFactory $contextFactory,
        private readonly Logger $logger
    ) {
    }

    #[Route(path: '/klarna/callback/notification/{transaction_id}', name: 'widgets.klarna.callback.notification', defaults: [
        'csrf_protected' => false,
        'XmlHttpRequest' => true
    ], methods: ['POST'])]
    public function notificationCallback(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $this->notificationCallback->handle($request->get('transaction_id'), (string)$request->get('event_type'), $salesChannelContext);

        return new Response();
    }

    #[Route(path: '/klarna/callback/authorization/{cart_token}', name: 'widgets.klarna.callback.authorization', defaults: [
        'csrf_protected' => false,
        'XmlHttpRequest' => true
    ], methods: ['POST'])]
    public function authorizationCallback(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $token = $request->attributes->getString('cart_token');

        try {
            $customerPayload = $this->contextPersister->load($token, $salesChannelContext->getSalesChannel()->getId());
            $customerContext = $this->contextFactory->create($token, $salesChannelContext->getSalesChannel()->getId(), $customerPayload);

            $this->authorizationCallback->handle($request->request->getString("authorization_token"), $customerContext);
        } catch (Throwable $exception) {
            $this->logger->error("authorizationCallback", ["exception" => $exception->getMessage(), "stack" => $exception->getTrace()]);

            return new Response("", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response();
    }

    #[\Symfony\Component\Routing\Annotation\Route(path: '/klarna/authorization-completed', name: 'klarna.authorization.complete', defaults: [
        'csrf_protected' => false,
        'XmlHttpRequest' => true
    ], methods: ['POST'])]
    public function authorizationCompleted(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        try {
            if (($token = $request->get('token')) && !empty($token)) {
                return new JsonResponse(["isAuthorized" => $this->authorizationCallback->find($token, $salesChannelContext->getContext())]);
            }
        } catch (Throwable $exception) {
            $this->logger->error("authorizationCompleted", ["exception" => $exception->getMessage()]);

            return new Response("", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        throw new BadRequestException("authorization token not found");
    }
}
