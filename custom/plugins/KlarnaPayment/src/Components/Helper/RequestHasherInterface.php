<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper;

use KlarnaPayment\Components\Client\Request\RequestInterface;

interface RequestHasherInterface
{
    public function getHash(RequestInterface $request, int $version = 1): string;
}
