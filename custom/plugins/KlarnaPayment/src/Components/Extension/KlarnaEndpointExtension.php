<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Extension;

use Shopware\Core\Framework\Struct\Struct;

class KlarnaEndpointExtension extends Struct
{
    public const EXTENSION_NAME = 'klarna_endpoint';

    /** @var string */
    protected $endpoint = '';

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
