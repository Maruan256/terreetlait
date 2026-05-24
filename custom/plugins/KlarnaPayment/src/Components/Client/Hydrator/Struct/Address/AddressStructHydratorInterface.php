<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\Address;

use KlarnaPayment\Components\Client\Struct\Address;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface AddressStructHydratorInterface
{
    public const TYPE_BILLING  = 'billing';
    public const TYPE_SHIPPING = 'shipping';

    public function hydrateFromContext(SalesChannelContext $context, string $type = self::TYPE_BILLING): ?Address;

    public function hydrateFromOrderAddress(?OrderAddressEntity $address, ?OrderCustomerEntity $customer): ?Address;

    public function hydrateFromResponse(array $address, Context $context): Address;
}
