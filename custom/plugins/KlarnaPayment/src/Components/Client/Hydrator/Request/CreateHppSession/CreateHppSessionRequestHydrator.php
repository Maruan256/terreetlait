<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateHppSession;

use KlarnaPayment\Components\Client\Request\CreateHppSessionRequest;
use KlarnaPayment\Components\Client\Struct\Options;
use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use KlarnaPayment\Components\Struct\Configuration;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession\UpdateSessionRequestHydratorInterface;

use Monolog\Logger;
use Shopware\Core\Framework\Api\ApiException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Request;

use Shopware\Core\PlatformRequest;

class CreateHppSessionRequestHydrator implements CreateHppSessionRequestHydratorInterface
{
    /** @var PaymentHelperInterface */
    private $paymentHelper;

    /** @var RouterInterface */
    private $router;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var Logger */
    private $logger;

    public function __construct(
        PaymentHelperInterface $paymentHelper,
        RouterInterface $router,
        ConfigReaderInterface $configReader,
        Logger $logger
    ) {
        $this->paymentHelper            = $paymentHelper;
        $this->router                   = $router;
        $this->configReader             = $configReader;
        $this->logger                   = $logger;
    }

    public function hydrate(array $data, Request $request, SalesChannelContext $salesChannelContext): CreateHppSessionRequest
    {
        if(!$request->headers->has(PlatformRequest::HEADER_ACCESS_KEY)) {
            throw ApiException::unauthorized(
                 'header',
                 \sprintf('Header "%s" is required.', PlatformRequest::HEADER_ACCESS_KEY)
             );
        }

        $swAccessKey = $request->headers->get(PlatformRequest::HEADER_ACCESS_KEY);
        $configuration  = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $sessionId = $data[UpdateSessionRequestHydratorInterface::KLARNA_SESSION_ID];
        $orderId = $request->get('orderId');
        $endpointRegion = $this->paymentHelper->getShippingCountry($salesChannelContext)->getIso();

        $options = new Options();
        $options->assign([
            'place_order_mode' => CreateHppSessionRequest::PLACE_ORDER_MODE_PLACE_ORDER,
            'purchase_type' => CreateHppSessionRequest::PURCHASE_TYPE_BUY,
            'show_subtotal_detail' => 'HIDE'
        ]);

        $createHppSessionRequest = new CreateHppSessionRequest();
        $createHppSessionRequest->assign([
            'options'          => $options,
            'salesChannel' => $salesChannelContext->getSalesChannel()->getId(),
            'merchantUrls' => $this->getMerchantUrls($orderId, $swAccessKey),
            'sessionId' => $sessionId,
            'endpointBaseUrl' => $this->getEndpointBaseUrl($configuration, $endpointRegion),
            'purchaseCountry'  => $this->paymentHelper->getShippingCountry($salesChannelContext)->getIso(),
        ]);

        return $createHppSessionRequest;
    }

    private function getMerchantUrls(string $orderId, string $storeApiToken): array
    {
        $backUrl = $this->router->generate(
            'store-api.klarna.callback.hpp-back',
            [
                'order_id' => $orderId
            ],
            RouterInterface::ABSOLUTE_URL
        );

        $cancelUrl = $this->router->generate(
            'store-api.klarna.callback.hpp-cancel',
            [
                'order_id' => $orderId
            ],
            RouterInterface::ABSOLUTE_URL
        );

        $errorUrl = $this->router->generate(
            'store-api.klarna.callback.hpp-error',
            [
                'order_id' => $orderId
            ],
            RouterInterface::ABSOLUTE_URL
        );

        $failureUrl = $this->router->generate(
            'store-api.klarna.callback.hpp-failure',
            [
                'order_id' => $orderId
            ],
            RouterInterface::ABSOLUTE_URL
        );

        $successUrl = $this->router->generate(
            'store-api.klarna.callback.hpp-success',
            [
                'order_id' => $orderId
            ],
            RouterInterface::ABSOLUTE_URL
        );

        return [
            'back' => $backUrl . "?" . PlatformRequest::HEADER_ACCESS_KEY . "=" . $storeApiToken,
            'cancel' => $cancelUrl . "?" . PlatformRequest::HEADER_ACCESS_KEY . "=" . $storeApiToken,
            'error' => $errorUrl . "?" . PlatformRequest::HEADER_ACCESS_KEY . "=" . $storeApiToken,
            'failure' => $failureUrl . "?" . PlatformRequest::HEADER_ACCESS_KEY . "=" . $storeApiToken,
            'success' => $successUrl . "?" . PlatformRequest::HEADER_ACCESS_KEY . "=" . $storeApiToken
        ];
    }

    private function getEndpointBaseUrl(
        Configuration $configuration,
        string $endpointRegion
    ): string {
        $testMode = $configuration->get('testMode', true);

        $baseUrl = '';
        if ($testMode) {
            switch ($endpointRegion) {
                case PaymentMethodInstaller::KLARNA_API_REGION_US:
                    $baseUrl = 'https://api-na.playground.klarna.com';

                    break;
                default:
                    $baseUrl = 'https://api.playground.klarna.com';

                    break;
            }
        } else {
            switch ($endpointRegion) {
                case PaymentMethodInstaller::KLARNA_API_REGION_US:
                    $baseUrl = 'https://api-na.klarna.com';

                    break;
                default:
                    $baseUrl = 'https://api.klarna.com';

                    break;
            }
        }

        return $baseUrl;
    }
}
