<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

class CreateExpressOrderRequest extends CreateOrderRequest implements RequestInterface
{
    public function jsonSerialize(): array
    {
        $json = parent::jsonSerialize();

        unset($json['billing_address']);

        return $json;
    }
}
