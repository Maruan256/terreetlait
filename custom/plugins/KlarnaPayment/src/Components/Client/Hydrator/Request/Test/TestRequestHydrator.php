<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\Test;

use KlarnaPayment\Components\Client\Request\TestRequest;

class TestRequestHydrator implements TestRequestHydratorInterface
{
    public function hydrate(string $username, string $password, bool $testMode, ?string $salesChannel): TestRequest
    {
        $request = new TestRequest();

        $request->assign([
            'username'      => $username,
            'password'      => $password,
            'testMode'      => $testMode,
            'salesChannel'  => $salesChannel,
            'klarnaOrderId' => '949a8de4-1f26-4cbb-9759-acba33e41d42',
        ]);

        return $request;
    }
}
