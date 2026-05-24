<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateCapture;

use KlarnaPayment\Components\Client\Request\CreateCaptureRequest;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface CreateCaptureRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag, Context $context): CreateCaptureRequest;
}
