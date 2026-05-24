<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Response;

interface ResponseInterface
{
    public function getHttpStatus(): int;

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array;
}
