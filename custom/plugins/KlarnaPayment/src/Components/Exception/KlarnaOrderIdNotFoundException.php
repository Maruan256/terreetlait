<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Exception;

class KlarnaOrderIdNotFoundException extends \LogicException
{
    public function __construct()
    {
        parent::__construct('Could not locate the klarna_order_id field in any order transaction');
    }
}
