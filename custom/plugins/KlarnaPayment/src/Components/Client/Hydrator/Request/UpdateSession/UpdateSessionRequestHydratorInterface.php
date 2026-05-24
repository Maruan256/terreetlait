<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession;

use KlarnaPayment\Components\Client\Request\UpdateSessionRequest;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface UpdateSessionRequestHydratorInterface
{
    public const KLARNA_SESSION_ID = 'klarnaSessionId';
    public const KLARNA_CLIENT_TOKEN = 'klarnaClientToken';
    public const KLARNA_PAYMENT_METHOD_CATEGORIES = 'klarnaPaymentMethodCategories';
    public const KLARNA_ADDRESS_HASH = 'klarnaAddressHash';
    public const KLARNA_SELECTED_CATEGORY = "klarnaSelectedCategory";

    public function hydrate(string $sessionId, Cart $cart, SalesChannelContext $context): UpdateSessionRequest;
}
