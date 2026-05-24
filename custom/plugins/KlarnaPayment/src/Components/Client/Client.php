<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client;

use KlarnaPayment\Components\Client\Request\GetOrderRequest;
use KlarnaPayment\Components\Client\Request\RequestInterface;
use KlarnaPayment\Components\Client\Request\TestRequest;
use KlarnaPayment\Components\Client\Response\GenericResponse;
use KlarnaPayment\Components\Client\Response\ResponseInterface;
use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Extension\KlarnaEndpointExtension;
use KlarnaPayment\Components\Helper\EndpointHelper\EndpointHelperInterface;
use KlarnaPayment\Components\Struct\Configuration;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Monolog\Logger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\Framework\Uuid\Uuid;

class Client implements ClientInterface
{
    private const REQUEST_LOG_BLACKLIST = [
        GetOrderRequest::class,
    ];

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var PluginService */
    private $pluginService;

    /** @var EntityRepository */
    private $logRepository;

    /** @var Logger */
    private $logger;

    /** @var EndpointHelperInterface */
    private $endpointHelper;

    /** @var string */
    private $shopwareVersion;

    /** @var null|string */
    private $idempotencyKey;

    /** @var int */
    private $retryCounter = 0;

    public function __construct(
        ConfigReaderInterface $configReader,
        PluginService $pluginService,
        EntityRepository $logRepository,
        Logger $logger,
        string $shopwareVersion,
        EndpointHelperInterface $endpointHelper
    ) {
        $this->configReader    = $configReader;
        $this->pluginService   = $pluginService;
        $this->logRepository   = $logRepository;
        $this->logger          = $logger;
        $this->shopwareVersion = $shopwareVersion;
        $this->endpointHelper  = $endpointHelper;
    }

    public function request(RequestInterface $request, Context $context): GenericResponse
    {
        
        $configuration  = $this->configReader->read($request->getSalesChannel());
        $endpointRegion = $this->endpointHelper->resolveEndpointRegion($request);

        if ($request instanceof TestRequest && $context->hasExtension(KlarnaEndpointExtension::EXTENSION_NAME)) {
            /** @var KlarnaEndpointExtension $klarnaEndpoint */
            $klarnaEndpoint = $context->getExtension(KlarnaEndpointExtension::EXTENSION_NAME);
            $endpointRegion = $klarnaEndpoint->getEndpoint();
        }

        if ($configuration->get('debugMode')) {
            $this->logRequest($request, $configuration, $endpointRegion);
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);

        if ($request->getMethod() === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
        } elseif ($request->getMethod() === 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($request->getMethod() === 'PATCH') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        }

        if (!empty($request->jsonSerialize())) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request));
        }

        curl_setopt($curl, CURLOPT_URL, $this->getEndpoint($request, $configuration, $endpointRegion));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeaders($request, $configuration, $endpointRegion));
        curl_setopt($curl, CURLOPT_USERAGENT, $this->getUserAgent($request, $context));

        /** @var string $rawResponse */
        $rawResponse = (string) curl_exec($curl);
        $curlStatus  = curl_errno($curl);
        $httpStatus  = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $data = json_decode($rawResponse, true);

        if (empty($data)) {
            $data = [];
        }

        if ($configuration->get('debugMode')) {
            $this->logResponse($request, $curlStatus, $httpStatus, $data);
        }

        if ($curlStatus !== CURLE_OK) {
            return $this->retryRequest($request, $context);
        }

        if ($httpStatus === 500) {
            return $this->retryRequest($request, $context);
        }

        if (empty($rawResponse) && !$this->allowEmptyResponse($httpStatus)) {
            return $this->retryRequest($request, $context);
        }

        $response = new GenericResponse();
        $response->assign([
            'httpStatus' => $httpStatus,
            'response'   => $data,
        ]);

        $this->saveRequest($request, $response, $context);

        $this->resetIdempotencyKey();
        $this->resetRetryCounter();

        return $response;
    }

    private function getHeaders(RequestInterface $request, Configuration $configuration, string $endpointRegion): array
    {
        $headers = [
            'Klarna-Idempotency-Key: ' . $this->getIdempotencyKey(),
            'Accept: application/json',
            'Content-Type: application/json',
            'cache-control: no-cache',
        ];

        if ($configuration->get('testMode')) {
            switch ($endpointRegion) {
                case PaymentMethodInstaller::KLARNA_API_REGION_US:
                    $username = $configuration->get('testApiUsernameUS');
                    $password = $configuration->get('testApiPasswordUS');

                    break;
                default:
                    $username = $configuration->get('testApiUsername');
                    $password = $configuration->get('testApiPassword');

                    break;
            }
        } else {
            switch ($endpointRegion) {
                case PaymentMethodInstaller::KLARNA_API_REGION_US:
                    $username = $configuration->get('apiUsernameUS');
                    $password = $configuration->get('apiPasswordUS');

                    break;
                default:
                    $username = $configuration->get('apiUsername');
                    $password = $configuration->get('apiPassword');

                    break;
            }
        }

        if ($request instanceof TestRequest) {
            $username = $request->getUsername();
            $password = $request->getPassword();
        }

        $headers[] = 'Authorization: Basic ' . base64_encode(sprintf('%s:%s', $username, $password));

        return $headers;
    }

    private function getUserAgent(RequestInterface $request, Context $context): string
    {
        $plugin = $this->pluginService->getPluginByName('KlarnaPayment', $context);

        $headers = [
            'Shopware/' . $this->shopwareVersion,
            'Licence/CE', // TODO: use correct licence when available
            'KlarnaPayment/' . $plugin->getVersion(),
            'CallType/' . $this->getCallType($request),
        ];

        return implode(' ', $headers);
    }

    private function getCallType(RequestInterface $request): string
    {
        $array = explode('\\', get_class($request));

        return str_replace('Request', '', (string) end($array));
    }

    private function resetIdempotencyKey(): void
    {
        $this->idempotencyKey = null;
    }

    private function getIdempotencyKey(): string
    {
        if ($this->idempotencyKey === null) {
            $this->idempotencyKey = Uuid::randomHex();
        }

        return $this->idempotencyKey;
    }

    private function getEndpoint(
        RequestInterface $request,
        Configuration $configuration,
        string $endpointRegion
    ): string {
        $testMode = $configuration->get('testMode', true);

        if ($request instanceof TestRequest) {
            $testMode = $request->getTestMode();
        }

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

        return $baseUrl . $request->getEndpoint();
    }

    private function retryRequest(RequestInterface $request, Context $context): GenericResponse
    {
        ++$this->retryCounter;

        if ($this->retryCounter >= 3) {
            $response = new GenericResponse();
            $response->assign([
                'httpStatus' => 500,
            ]);

            $this->saveRequest($request, $response, $context);
            $this->resetRetryCounter();
            $this->resetIdempotencyKey();

            return $response;
        }

        return $this->request($request, $context);
    }

    private function resetRetryCounter(): void
    {
        $this->retryCounter = 0;
    }

    private function logRequest(RequestInterface $request, Configuration $configuration, string $endpointRegion): void
    {
        $data = json_encode($request, JSON_PRESERVE_ZERO_FRACTION);

        if (!empty($data)) {
            $data = json_decode($data, true);
        } else {
            $data = [];
        }

        $payload = [
            'method'         => $request->getMethod(),
            'endpoint'       => $this->getEndpoint($request, $configuration, $endpointRegion),
            'request'        => $data,
            'idempotencyKey' => $this->getIdempotencyKey(),
        ];

        if ($request instanceof TestRequest) {
            $payload['request'] = [];
        }

        $this->logger->debug('Request: ' . $this->getCallType($request), $payload);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function logResponse(RequestInterface $request, int $curlStatus, int $httpStatus, array $data): void
    {
        $payload = [
            'curlStatus'     => $curlStatus,
            'httpStatus'     => $httpStatus,
            'response'       => $data,
            'idempotencyKey' => $this->getIdempotencyKey(),
        ];

        $this->logger->debug('Response: ' . $this->getCallType($request), $payload);
    }

    private function saveRequest(RequestInterface $request, ResponseInterface $response, Context $context): void
    {
        if (in_array(get_class($request), self::REQUEST_LOG_BLACKLIST, true)) {
            return;
        }

        $orderId = $this->getOrderId($request, $response);

        if (empty($orderId)) {
            return;
        }

        $entry = [
            'id'             => Uuid::randomHex(),
            'klarnaOrderId'  => $orderId,
            'callType'       => $this->getCallType($request),
            'request'        => $request->jsonSerialize(),
            'response'       => $response->jsonSerialize(),
            'idempotencyKey' => $this->idempotencyKey,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($entry): void {
            $this->logRepository->upsert([$entry], $context);
        });
    }

    private function allowEmptyResponse(int $httpStatus): bool
    {
        $allowedStatus = [
            201,
            204,
        ];

        return in_array($httpStatus, $allowedStatus, true);
    }

    private function getOrderId(RequestInterface $request, ResponseInterface $response): string
    {
        $data = $response->jsonSerialize();

        if (array_key_exists('order_id', $data) && !empty($data['order_id'])) {
            return $data['order_id'];
        }

        if (array_key_exists('klarna_order_id', $data) && !empty($data['klarna_order_id'])) {
            return $data['klarna_order_id'];
        }

        if (method_exists($request, 'getKlarnaOrderId')) {
            return $request->getKlarnaOrderId();
        }

        return '';
    }
}
