<?php declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Storefront;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

abstract class AbstractKlarnaHppCallbackRoute
{
    abstract public function getDecorated(): AbstractKlarnaHppCallbackRoute;

    abstract public function load(Criteria $criteria, SalesChannelContext $context): StoreApiResponse;
}