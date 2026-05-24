<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\Customer;

use KlarnaPayment\Components\Client\Struct\Customer;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CustomerStructHydratorInterface
{
    public function hydrate(SalesChannelContext $context): ?Customer;
}
