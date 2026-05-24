<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper;

use KlarnaPayment\Components\Client\Request\RequestInterface;

class UpdateAddressRequestHasher implements RequestHasherInterface
{
    /** @var string */
    private $appSecret;

    public function __construct(string $appSecret)
    {
        $this->appSecret = $appSecret;
    }

    public function getHash(RequestInterface $request, int $version = 1): string
    {
        if (empty($this->appSecret)) {
            throw new \LogicException('empty app secret');
        }

        $json = \json_encode($request, JSON_PRESERVE_ZERO_FRACTION);

        if (empty($json)) {
            throw new \LogicException('could not generate hash');
        }

        return \hash_hmac('sha256', $json, $this->appSecret);
    }
}
