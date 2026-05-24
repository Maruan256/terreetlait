<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client;

use KlarnaPayment\Components\Client\Request\RequestInterface;
use KlarnaPayment\Components\Client\Response\GenericResponse;
use Shopware\Core\Framework\Context;

interface ClientInterface
{
    public function request(RequestInterface $request, Context $context): GenericResponse;
}
