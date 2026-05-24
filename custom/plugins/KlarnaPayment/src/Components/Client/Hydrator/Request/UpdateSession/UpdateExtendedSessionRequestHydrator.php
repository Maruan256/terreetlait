<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession;

use KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession\UpdateSessionRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession\UpdateExtendedSessionRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\Address\AddressStructHydratorInterface;
use KlarnaPayment\Components\Client\Request\UpdateSessionRequest;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class UpdateExtendedSessionRequestHydrator implements UpdateExtendedSessionRequestHydratorInterface
{
    /** @var UpdateSessionRequestHydratorInterface */
    private $updateSessionRequest;

    /** @var AddressStructHydratorInterface */
    private $addressStructHydrator;

    public function __construct(
        UpdateSessionRequestHydratorInterface $updateSessionRequest,
        AddressStructHydratorInterface $addressStructHydrator
    ) {
        $this->updateSessionRequest = $updateSessionRequest;
        $this->addressStructHydrator = $addressStructHydrator;
    }

    public function hydrate(string $sessionId, Cart $cart, SalesChannelContext $salesChannelContext): UpdateSessionRequest
    {
        $request = $this->updateSessionRequest->hydrate($sessionId, $cart, $salesChannelContext);

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
