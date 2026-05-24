<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateRefund;

use KlarnaPayment\Components\Client\Request\CreateRefundRequest;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface CreateRefundRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag, Context $context): CreateRefundRequest;
}
