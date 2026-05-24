<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Administration;

use KlarnaPayment\Components\Client\Client;
use KlarnaPayment\Components\Client\Hydrator\Request\Test\TestRequestHydratorInterface;
use KlarnaPayment\Components\Extension\KlarnaEndpointExtension;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Monolog\Logger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Route(defaults={"_routeScope": {"api"}})
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class SettingsController extends AbstractController
{
    /** @var Client */
    private $client;

    /** @var Logger */
    private $logger;

    /** @var TestRequestHydratorInterface */
    private $requestHydrator;

    public function __construct(
        Client $client,
        Logger $logger,
        TestRequestHydratorInterface $requestHydrator
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->requestHydrator = $requestHydrator;
    }

    /**
     * @Route("/api/_action/klarna_payment/fetch-eu-api-region-key", name="api.action.klarna_payment.fetch_eu_api_region_key", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/fetch-eu-api-region-key", name="api.action.klarna_payment.fetch_eu_api_region_key.legacy", methods={"POST"})
     */
    #[Route(path: '/api/_action/klarna_payment/fetch-eu-api-region-key', name: 'api.action.klarna_payment.fetch_eu_api_region_key', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/fetch-eu-api-region-key', name: 'api.action.klarna_payment.fetch_eu_api_region_key.legacy', methods: ['POST'])]
    public function getEuApiRegionKey(): JsonResponse
    {
        $result = [
            'key' => PaymentMethodInstaller::KLARNA_API_REGION_EU,
        ];

        return new JsonResponse(['status' => 'success', 'data' => $result], 200);
    }

    /**
     * @Route("/api/_action/klarna_payment/fetch-us-api-region-key", name="api.action.klarna_payment.fetch_us_api_region_key", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/fetch-us-api-region-key", name="api.action.klarna_payment.fetch_us_api_region_key.legacy", methods={"POST"})
     */
    #[Route(path: '/api/_action/klarna_payment/fetch-us-api-region-key', name: 'api.action.klarna_payment.fetch_us_api_region_key', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/fetch-us-api-region-key', name: 'api.action.klarna_payment.fetch_us_api_region_key.legacy', methods: ['POST'])]
    public function getUsaApiRegionKey(): JsonResponse
    {
        $result = [
            'key' => PaymentMethodInstaller::KLARNA_API_REGION_US,
        ];

        return new JsonResponse(['status' => 'success', 'data' => $result], 200);
    }

    /**
     * @Route("/api/_action/klarna_payment/validate-credentials", name="api.action.klarna_payment.validate.credentials", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/validate-credentials", name="api.action.klarna_payment.validate.credentials.legacy", methods={"POST"})
     */
    #[Route(path: '/api/_action/klarna_payment/validate-credentials', name: 'api.action.klarna_payment.validate.credentials', methods: ['POST'])]
    #[Route(path: '/api/v{version}/_action/klarna_payment/validate-credentials', name: 'api.action.klarna_payment.validate.credentials.legacy', methods: ['POST'])]
    public function validateCredentials(RequestDataBag $dataBag, Context $context): JsonResponse
    {
        $testMode = $dataBag->get('testMode', false);

        if ($testMode === false) {
            $credentialsValid = $this->validate(
                $dataBag->get('apiUsername', ''),
                $dataBag->get('apiPassword', ''),
                false,
                $dataBag->get('salesChannel'),
                $dataBag->get('endpoint'),
                $context
            );
        } else {
            $credentialsValid = $this->validate(
                $dataBag->get('testApiUsername', ''),
                $dataBag->get('testApiPassword', ''),
                true,
                $dataBag->get('salesChannel'),
                $dataBag->get('endpoint'),
                $context
            );
        }

        if (!$credentialsValid) {
            return new JsonResponse(['status' => 'error', 'mode' => $testMode ? 'test' : 'live'], 400);
        }

        return new JsonResponse(['status' => 'success'], 200);
    }

    #[Route(path: '/api/_action/klarna_payment/get-old-ids', name: 'api.action.klarna_payment.deprecated.ids', methods: ['GET'])]
    public function getPaymentIds(): JsonResponse
    {
        return new JsonResponse(["ids" => PaymentMethodInstaller::KLARNA_DEPRECATED_IDS]);
    }

    private function validate(
        string $username,
        string $password,
        bool $testMode,
        ?string $salesChannel,
        string $endpoint,
        Context $context
    ): bool {
        $request = $this->requestHydrator->hydrate($username, $password, $testMode, $salesChannel);

        $klarnaEndpoint = (new KlarnaEndpointExtension())->assign(['endpoint' => $endpoint]);
        $context->addExtension(KlarnaEndpointExtension::EXTENSION_NAME, $klarnaEndpoint);

        $response = $this->client->request($request, $context);

        $status = $response->getHttpStatus() === 404 && $response->getResponse()['error_code'] === 'NO_SUCH_ORDER';

        $this->logger->info('klarna plugin credentials validated', [
            'success' => $status,
            'salesChannel' => $salesChannel ?? 'all',
            'response' => $response,
        ]);

        return $status;
    }
}
