<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\GetHppSessionDetails;

use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Components\Client\Request\GetHppSessionDetailsRequest;

use Monolog\Logger;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GetHppSessionDetailsRequestHydrator implements GetHppSessionDetailsRequestHydratorInterface
{
    /** @var PaymentHelperInterface */
    private $paymentHelper;

    /** @var Logger */
    protected $logger;

    public function __construct(PaymentHelperInterface $paymentHelper, Logger $logger)
    {
        $this->paymentHelper            = $paymentHelper;
        $this->logger = $logger;
    }
    public function hydrate(string $sessionId, SalesChannelContext $salesChannelContext, string $countryIso = ''): GetHppSessionDetailsRequest
    {
        $request = new GetHppSessionDetailsRequest();

        $request->assign([
            'purchaseCountry'  => $countryIso,
            'sessionId' => $sessionId
        ]);

        return $request;
    }
}
