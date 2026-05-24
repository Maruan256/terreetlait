<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\CartHasher\CartHasherInterface;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Components\Validator\CartHash;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderValidationEventListener implements EventSubscriberInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var PaymentHelperInterface */
    private $paymentHelper;

    /** @var CartService */
    private $cartService;

    /** @var CartHasherInterface */
    private $cartHasher;

    public function __construct(
        RequestStack $requestStack,
        PaymentHelperInterface $paymentHelper,
        CartService $cartService,
        CartHasherInterface $cartHasher
    ) {
        $this->requestStack  = $requestStack;
        $this->paymentHelper = $paymentHelper;
        $this->cartService   = $cartService;
        $this->cartHasher    = $cartHasher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'framework.validation.order.create' => 'validateOrderData',
        ];
    }

    public function validateOrderData(BuildValidationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        $context = $this->getContextFromRequest($request);

        if ($this->paymentHelper->isKlarnaPaymentsSelected($context)) {
            $cart = $this->cartService->getCart($context->getToken(), $context);
            $hash = $this->cartHasher->generate($cart, $context);

            $event->getDefinition()->add(
                'klarnaAuthorizationToken',
                new NotBlank()
            );

            $event->getDefinition()->add(
                'klarnaCartHash',
                new CartHash(['value' => $hash])
            );
        }
    }

    private function getContextFromRequest(Request $request): SalesChannelContext
    {
        return $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
    }
}
