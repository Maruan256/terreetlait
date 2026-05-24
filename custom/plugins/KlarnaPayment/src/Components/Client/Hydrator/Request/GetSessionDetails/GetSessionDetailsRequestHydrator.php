<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\GetSessionDetails;

use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Components\Client\Request\GetSessionDetailsRequest;

use Monolog\Logger;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GetSessionDetailsRequestHydrator implements GetSessionDetailsRequestHydratorInterface
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
    public function hydrate(string $sessionId, SalesChannelContext $salesChannelContext): GetSessionDetailsRequest
    {
        $request = new GetSessionDetailsRequest();

        $request->assign([
            'purchaseCountry'  => $this->paymentHelper->getShippingCountry($salesChannelContext)->getIso(),
            'sessionId' => $sessionId
        ]);

        return $request;
    }
}
