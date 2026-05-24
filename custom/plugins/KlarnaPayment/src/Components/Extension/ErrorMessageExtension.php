<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Extension;

use Shopware\Core\Framework\Struct\Struct;

class ErrorMessageExtension extends Struct
{
    public const EXTENSION_NAME = 'klarna_error';
    public const GENERIC_ERROR  = 'genericError';

    /** @var string */
    protected $message = '';

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return 'KlarnaPayment.errorMessages.' . $this->message;
    }
}
