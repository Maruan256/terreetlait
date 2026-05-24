<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

class TestRequest extends GetOrderRequest
{
    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var bool */
    protected $testMode;

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getTestMode(): bool
    {
        return $this->testMode;
    }
}
