<?php /** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace KlarnaPayment\Components\Callback;

use http\Exception\RuntimeException;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Cart\KlarnaCartEntity;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtension;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenFactoryInterfaceV2;
use Shopware\Core\Checkout\Payment\PaymentProcessor;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use Monolog\Logger;

class AuthorizationCallback
{
    public const AUTHORIZATION_TOKEN_FIELD = 'klarna_authorization_token';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly PaymentProcessor $paymentProcessor,
        private readonly RequestStack $requestStack,
        private readonly EntityRepository $cartDataRepository,
        private readonly Logger $logger,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly EntityRepository $orderRepository,
        private readonly TokenFactoryInterfaceV2 $tokenFactory
    ) {
    }

    /**
     * @throws ConstraintViolationException
     * @throws Throwable
     */
    public function handle(string $authorizationToken, SalesChannelContext $context): void
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('customFields.' . self::AUTHORIZATION_TOKEN_FIELD, $authorizationToken));

        $result = $this->orderTransactionRepository->search($criteria, $context->getContext())->getTotal();

        if ($result > 0) {
            $this->logger->warning('tried to start authorization-callback more times', [
                'authorizationToken' => $authorizationToken
            ]);

            return;
        }

        $this->logger->debug('start authorization-callback', [
            'authorizationToken' => $authorizationToken,
            'customer' => $context->getCustomer()
        ]);

        $dataBag = new RequestDataBag([
            'tos' => true,
            'klarnaAuthorizationToken' => $authorizationToken,
        ]);

        $criteria = (new Criteria())->addFilter(new EqualsFilter('cartToken', $context->getToken()));

        /** @var KlarnaCartEntity $cartData */
        $cartData = $this->cartDataRepository->search($criteria, $context->getContext())->first();

        if ($cartData instanceof KlarnaCartEntity && !empty($cartData->getPayload())) {
            $dataBag->add($cartData->getPayload());
        }

        $currentRequest = $this->requestStack->getCurrentRequest();

        if (null === $currentRequest) {
            $this->logger->warning('authorization-callback: current request is empty', [
                'authorizationToken' => $authorizationToken,
                'dataBag' => $dataBag
            ]);

            throw new RuntimeException('Current request is null in requestStack');
        }

        $currentRequest->request->add($dataBag->all());

        $orderId = $this->orderService->createOrder($dataBag, $context);
        $response = $this->paymentProcessor->pay($orderId, $currentRequest, $context);

        if (null === $response) {
            $this->logger->warning('authorization-callback: handle payment response is empty', [
                'authorizationToken' => $authorizationToken,
                'orderId' => $orderId,
            ]);

            throw new RuntimeException('Empty response for handling payment');
        }

        $this->logger->debug('authorization-callback: order created via authorization callback.', [
            'orderId' => $orderId,
            'authorizationToken' => $authorizationToken,
        ]);

        $token = explode("_sw_payment_token=", $response->getTargetUrl())[1] ?? null;

        if (null === $token) {
            $this->logger->error('authorization-callback: token', [
                'authorizationToken' => $authorizationToken,
                'orderId' => $orderId,
                'request' => $currentRequest
            ]);

            throw new RuntimeException('Empty response for authorization callback');
        }

        $paymentToken = $this->tokenFactory->parseToken($token);

        $this->paymentProcessor->finalize($paymentToken, $currentRequest, $context);

        if ($cartData instanceof KlarnaCartEntity) {
            $this->cartDataRepository->delete([['id' => $cartData->getUniqueIdentifier()]], $context->getContext());
        }
    }

    public function find(string $authorizationToken, Context $context): bool
    {
        $criteria = (new Criteria())
            ->addAssociation(OrderExtension::EXTENSION_NAME)
            ->addFilter(new EqualsFilter(OrderExtension::EXTENSION_NAME . '.authorizationToken',
                $authorizationToken));

        return $this->orderRepository->searchIds($criteria, $context)->firstId() !== null;
    }
}
