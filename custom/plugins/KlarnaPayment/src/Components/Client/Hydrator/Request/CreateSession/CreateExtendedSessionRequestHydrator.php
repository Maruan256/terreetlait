<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateSession;

use KlarnaPayment\Components\Client\Hydrator\Struct\Address\AddressStructHydratorInterface;
use KlarnaPayment\Components\Client\Request\CreateSessionRequest;

use Monolog\Logger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CreateExtendedSessionRequestHydrator implements CreateExtendedSessionRequestHydratorInterface
{
    /** @var CreateSessionRequestHydratorInterface */
    private $createSessionRequest;

    /** @var AddressStructHydratorInterface */
    private $addressStructHydrator;

    /** @var Logger */
    private $logger;

    public function __construct(
        CreateSessionRequestHydratorInterface $createSessionRequest,
        AddressStructHydratorInterface $addressStructHydrator,
        Logger $logger
    ) {
        $this->createSessionRequest = $createSessionRequest;
        $this->addressStructHydrator = $addressStructHydrator;
        $this->logger = $logger;
    }

    public function hydrate(Cart $cart, SalesChannelContext $salesChannelContext): CreateSessionRequest
    {
        $request = $this->createSessionRequest->hydrate($cart, $salesChannelContext);

        $billingAddress = $this->addressStructHydrator->hydrateFromContext($salesChannelContext, AddressStructHydratorInterface::TYPE_BILLING);

        if (!empty($billingAddress)) {
            $request->assign([
                'billingAddress' => $billingAddress,
            ]);
        }

        $shippingAddress = $this->addressStructHydrator->hydrateFromContext($salesChannelContext, AddressStructHydratorInterface::TYPE_SHIPPING);

        if (!empty($shippingAddress)) {
            $request->assign([
                'shippingAddress' => $shippingAddress,
            ]);
        }
        
        return $request;
    }
}
