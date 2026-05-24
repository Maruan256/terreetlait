<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\SignInWithKlarnaHelper;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;

use KlarnaPayment\Components\Client\Request\RequestInterface;

interface SignInWithKlarnaHelperInterface
{
    public function requestCustomerAccessToken(RequestInterface $request, SalesChannelContext $salesChannelContext);

    public function collectedAddressData(CustomerEntity $customer, RequestDataBag $customerData, SalesChannelContext $salesChannelContext);

    public function getCustomerByEmail(string $email, SalesChannelContext $salesChannelContext);

    public function addressDataIdentical(CustomerAddressEntity $customerAddressEntity): bool;
}
