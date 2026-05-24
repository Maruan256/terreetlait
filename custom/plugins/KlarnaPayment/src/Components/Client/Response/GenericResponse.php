<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Response;

use Shopware\Core\Framework\Struct\Struct;

class GenericResponse extends Struct implements ResponseInterface
{
    /** @var int */
    protected $httpStatus = 200;

    /** @var array<string,mixed> */
    protected $response = [];

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * @return array<string,mixed>
     */
    public function getResponse(): array
    {
        return $this->response;
    }
}
