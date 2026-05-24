<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Administration;

use KlarnaPayment\Components\Exception\GetKlarnaOrderException;
use KlarnaPayment\Components\Exception\KlarnaOrderIdNotFoundException;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Components\Helper\OrderValidator\OrderValidatorInterface;
use KlarnaPayment\Exception\OrderUpdateDeniedException;
use Monolog\Logger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Route(defaults={"_routeScope": {"api"}})
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class OrderUpdateController extends AbstractController
{
    public function __construct(
        private readonly OrderFetcherInterface $orderFetcher,
        private readonly OrderValidatorInterface $orderValidator,
        private readonly Logger $logger
    ) {
    }

    /**
     * @Route("/api/_action/klarna_payment/update_order", name="api.action.klarna_payment.order_update.update", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/update_order", name="api.action.klarna_payment.order_update.update.legacy", methods={"POST"})
     *
     * @throws OrderUpdateDeniedException
     *
     * @see \KlarnaPayment\Components\EventListener\OrderChangeEventListener::validateKlarnaOrder Change accordingly to keep functionality synchronized
     */
    #[Route(path: '/api/_action/klarna_payment/update_order', name: 'api.action.klarna_payment.order_update.update', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/update_order', name: 'api.action.klarna_payment.order_update.update.legacy', methods: ['POST'])]
    public function update(RequestDataBag $dataBag, Context $context): JsonResponse
    {
        $orderId = $dataBag->get('orderId', '');

        try {
            $orderEntity = $this->orderFetcher->getOrderFromOrder(Uuid::fromHexToBytes($orderId), $context);
        } catch (InvalidUuidException $e) {
            return new JsonResponse(['status' => 'success'], 200);
        }

        if (!$orderEntity) {
            return new JsonResponse(['status' => 'success'], 200);
        }

        if (!$this->orderValidator->isKlarnaOrder($orderEntity)) {
            return new JsonResponse(['status' => 'success'], 200);
        }

        try {
            if (!$this->orderValidator->validateAndInitLineItemsHash($orderEntity, $context)
                || !$this->orderValidator->validateAndInitOrderAddressHash($orderEntity, null, $context)) {
                throw new OrderUpdateDeniedException($orderId);
            }
        } catch (GetKlarnaOrderException $exception) {
            $this->logger->error("Validate lineItemHash", ["code" => $exception->getErrorCode(), "response" => $exception->getResponse()]);
        } catch (KlarnaOrderIdNotFoundException $exception) {
            $this->logger->error("Validate orderAddressHash", ["message" => $exception->getMessage()]);
        }

        return new JsonResponse(['status' => 'success'], 200);
    }
}
