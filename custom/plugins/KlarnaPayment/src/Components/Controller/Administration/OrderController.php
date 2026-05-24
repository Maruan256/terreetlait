<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Administration;

use KlarnaPayment\Components\Client\Client;
use KlarnaPayment\Components\Client\Hydrator\Request\CancelPayment\CancelPaymentRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\CreateCapture\CreateCaptureRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\CreateRefund\CreateRefundRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\ExtendAuthorization\ExtendAuthorizationRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\GetOrder\GetOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\ReleaseRemainingAuthorization\ReleaseRemainingAuthorizationHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Response\GetOrder\GetOrderResponseHydratorInterface;
use KlarnaPayment\Components\Client\Response\GetOrderResponse;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\RequestLog\RequestLogEntity;
use Monolog\Logger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Route(defaults={"_routeScope": {"api"}})
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class OrderController extends AbstractController
{
    /** @var Client */
    private $client;

    /** @var EntityRepository */
    private $requestLogRepository;

    /** @var GetOrderRequestHydratorInterface */
    private $getOrderRequestHydrator;

    /** @var GetOrderResponseHydratorInterface */
    private $getOrderResponseHydrator;

    /** @var CreateCaptureRequestHydratorInterface */
    private $captureRequestHydrator;

    /** @var CreateRefundRequestHydratorInterface */
    private $refundRequestHydrator;

    /** @var CancelPaymentRequestHydratorInterface */
    private $cancelPaymentRequestHydrator;

    /** @var ReleaseRemainingAuthorizationHydratorInterface */
    private $remainingAuthorizationHydrator;

    /** @var ExtendAuthorizationRequestHydratorInterface */
    private $authorizationRequestHydrator;

    /** @var OrderTransactionStateHandler */
    private $stateHandler;

    /** @var Logger */
    private $logger;

    /** @var EntityRepository */
    private $orderTransactionRepository;

    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    public function __construct(
        Client $client,
        EntityRepository $requestLogRepository,
        GetOrderRequestHydratorInterface $getOrderRequestHydrator,
        GetOrderResponseHydratorInterface $getOrderResponseHydrator,
        CreateCaptureRequestHydratorInterface $captureRequestHydrator,
        CreateRefundRequestHydratorInterface $refundRequestHydrator,
        CancelPaymentRequestHydratorInterface $cancelPaymentRequestHydrator,
        ReleaseRemainingAuthorizationHydratorInterface $remainingAuthorizationHydrator,
        ExtendAuthorizationRequestHydratorInterface $authorizationRequestHydrator,
        OrderTransactionStateHandler $stateHandler,
        Logger $logger,
        EntityRepository $orderTransactionRepository,
        StateMachineRegistry $stateMachineRegistry
    ) {
        $this->client                         = $client;
        $this->requestLogRepository           = $requestLogRepository;
        $this->captureRequestHydrator         = $captureRequestHydrator;
        $this->refundRequestHydrator          = $refundRequestHydrator;
        $this->getOrderRequestHydrator        = $getOrderRequestHydrator;
        $this->getOrderResponseHydrator       = $getOrderResponseHydrator;
        $this->cancelPaymentRequestHydrator   = $cancelPaymentRequestHydrator;
        $this->remainingAuthorizationHydrator = $remainingAuthorizationHydrator;
        $this->authorizationRequestHydrator   = $authorizationRequestHydrator;
        $this->stateHandler                   = $stateHandler;
        $this->logger                         = $logger;
        $this->orderTransactionRepository     = $orderTransactionRepository;
        $this->stateMachineRegistry           = $stateMachineRegistry;
    }

    /**
     * @Route("/api/_action/klarna_payment/fetch_order", name="api.action.klarna_payment.order.fetch", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/fetch_order", name="api.action.klarna_payment.order.fetch.legacy", methods={"POST"})
     */
    #[Route(path: '/api/_action/klarna_payment/fetch_order', name: 'api.action.klarna_payment.order.fetch', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/fetch_order', name: 'api.action.klarna_payment.order.fetch.legacy', methods: ['POST'])]
    public function fetchOrder(RequestDataBag $dataBag, Context $context): JsonResponse
    {
        $response = $this->getOrderResponse($dataBag, $context);

        if (!$response) {
            return new JsonResponse(['status' => 'error'], 400);
        }

        $history = $this->getHistory($dataBag->get('klarna_order_id'), $context);

        return new JsonResponse(['order' => $response, 'transactionHistory' => $history], 200);
    }

    /**
     * @Route("/api/_action/klarna_payment/capture_order", name="api.action.klarna_payment.order.capture", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/capture_order", name="api.action.klarna_payment.order.capture.legacy", methods={"POST"})
     */
    #[Route(path: '/api/_action/klarna_payment/capture_order', name: 'api.action.klarna_payment.order.capture', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/capture_order', name: 'api.action.klarna_payment.order.capture.legacy', methods: ['POST'])]
    public function captureOrder(RequestDataBag $dataBag, Context $context): JsonResponse
    {
        $request  = $this->captureRequestHydrator->hydrate($dataBag, $context);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() !== 201) {
            return new JsonResponse(['status' => 'error'], 400);
        }

        try {
            if ($dataBag->get('complete')) {
                if ($this->isPartiallyPaid($dataBag->get('orderTransactionId'), $context)) {
                    // If the previous state is "paid_partially", "paid" is currently not allowed as direct transition, see https://github.com/shopwareLabs/SwagPayPal/blob/b63efb9/src/Util/PaymentStatusUtil.php#L79
                    $this->stateHandler->process($dataBag->get('orderTransactionId'), $context);
                    $this->stateHandler->paid($dataBag->get('orderTransactionId'), $context);
                } else {
                    $this->stateHandler->paid($dataBag->get('orderTransactionId'), $context);
                }
            } else {
                $this->stateHandler->payPartially($dataBag->get('orderTransactionId'), $context);
            }
        } catch (IllegalTransitionException $exception) {
            // we can not ensure that the capture or capture partially status change is allowed

            $this->logger->notice($exception->getMessage(), $exception->getParameters());
        }

        return new JsonResponse(['status' => 'success'], 200);
    }

    /**
     * @Route("/api/_action/klarna_payment/refund_order", name="api.action.klarna_payment.order.refund", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/refund_order", name="api.action.klarna_payment.order.refund.legacy", methods={"POST"})
     */
    #[Route(path: '/api/_action/klarna_payment/refund_order', name: 'api.action.klarna_payment.order.refund', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/refund_order', name: 'api.action.klarna_payment.order.refund.legacy', methods: ['POST'])]
    public function refundOrder(RequestDataBag $dataBag, Context $context): JsonResponse
    {
        $request  = $this->refundRequestHydrator->hydrate($dataBag, $context);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() !== 201) {
            return new JsonResponse(['status' => 'error'], 400);
        }

        try {
            if ($dataBag->get('complete')) {
                $this->stateHandler->refund($dataBag->get('orderTransactionId'), $context);
            } else {
                $this->stateHandler->refundPartially($dataBag->get('orderTransactionId'), $context);
            }
        } catch (IllegalTransitionException $exception) {
            // we can not ensure that the refund or refund partially status change is allowed

            $this->logger->notice($exception->getMessage(), $exception->getParameters());
        }

        return new JsonResponse(['status' => 'success'], 200);
    }

    /**
     * @Route("/api/_action/klarna_payment/extend_authorization", name="api.action.klarna_payment.extend.authorization", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/extend_authorization", name="api.action.klarna_payment.extend.authorization.legacy", methods={"POST"})
     */
    #[Route(path: '/api/_action/klarna_payment/extend_authorization', name: 'api.action.klarna_payment.extend.authorization', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/extend_authorization', name: 'api.action.klarna_payment.extend.authorization.legacy', methods: ['POST'])]
    public function extendAuthorizationTime(RequestDataBag $dataBag, Context $context): JsonResponse
    {
        $request  = $this->authorizationRequestHydrator->hydrate($dataBag);
        $response = $this->client->request($request, $context);


        
        if ($response->getHttpStatus() !== 204) {
            return new JsonResponse(['status' => 'error'], 400);
        }
        
        return new JsonResponse(['status' => 'success'], 200);
    }

    /**
     * @Route("/api/_action/klarna_payment/cancel_payment", name="api.action.klarna_payment.cancel.payment", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/cancel_payment", name="api.action.klarna_payment.cancel.payment.legacy", methods={"POST"})
     */
    #[Route(path: '/api/_action/klarna_payment/cancel_payment', name: 'api.action.klarna_payment.cancel.payment', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/cancel_payment', name: 'api.action.klarna_payment.cancel.payment.legacy', methods: ['POST'])]
    public function cancelPayment(RequestDataBag $dataBag, Context $context): JsonResponse
    {   
        $request  = $this->cancelPaymentRequestHydrator->hydrate($dataBag);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() === 204) {
            $this->stateHandler->cancel($dataBag->get('orderTransactionId'), $context);

            return new JsonResponse(['success' => 'true'], 200);
        }

        return new JsonResponse(['success' => 'false'], 400);
        
    }

    /**
     * @Route("/api/_action/klarna_payment/release_remaining_authorization", name="api.action.klarna_payment.release.remaining.authorization", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/release_remaining_authorization", name="api.action.klarna_payment.release.remaining.authorization.legacy", methods={"POST"})
     */
    #[Route(path: '/api/_action/klarna_payment/release_remaining_authorization', name: 'api.action.klarna_payment.release.remaining.authorization', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/release_remaining_authorization', name: 'api.action.klarna_payment.release.remaining.authorization.legacy', methods: ['POST'])]
    public function releaseRemainingAuthorization(RequestDataBag $dataBag, Context $context): JsonResponse
    {
        $request  = $this->remainingAuthorizationHydrator->hydrate($dataBag);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() === 204) {
            return new JsonResponse(['status' => 'success'], 200);
        }

        return new JsonResponse(['status' => 'error'], 400);
    }

    private function getOrderResponse(RequestDataBag $dataBag, Context $context): ?GetOrderResponse
    {
        $request  = $this->getOrderRequestHydrator->hydrate($dataBag);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() !== 200) {
            return null;
        }

        return $this->getOrderResponseHydrator->hydrate($response, $context);
    }

    /**
     * @return array<int,mixed>
     */
    private function getHistory(string $klarnaOrderId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('klarnaOrderId', $klarnaOrderId));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        $repositoryResult = $this->requestLogRepository->search($criteria, $context);

        $history = [];

        /** @var RequestLogEntity $element */
        foreach ($repositoryResult->getElements() as $element) {
            if ($element->getCreatedAt() === null) {
                continue;
            }

            $response     = $element->getResponse();
            $responseData = $response;

            if (is_array($response)) {
                if (array_key_exists('response', $response)) {
                    $responseData = $response['response'];
                }

                $error = array_key_exists('error_code', $responseData);
            } else {
                $error = false;
            }

            $history[] = [
                'date'       => $element->getCreatedAt()->format(DATE_ATOM),
                'message'    => $element->getCallType(),
                'request'    => json_encode($element->getRequest()),
                'response'   => json_encode($responseData),
                'statusCode' => $response['httpStatus'] ?? null,
                'error'      => $error,
            ];
        }

        return $history;
    }

    private function isPartiallyPaid(string $orderTransactionId, Context $context): bool
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($transaction === null || $transaction->getStateMachineState() === null) {
            return false;
        }

        return $transaction->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_PARTIALLY_PAID;
    }

    private function getOrderTransaction(string $orderTransactionId, Context $context): ?OrderTransactionEntity
    {
        return $this->orderTransactionRepository->search((new Criteria([$orderTransactionId]))->addAssociation('stateMachineState'), $context)->getEntities()->first();
    }
}
