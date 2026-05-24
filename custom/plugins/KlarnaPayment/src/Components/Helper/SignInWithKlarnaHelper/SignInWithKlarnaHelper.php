<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\SignInWithKlarnaHelper;

use KlarnaPayment\Components\Client\Request\RequestInterface;
use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Client\Response\GenericResponse;
use KlarnaPayment\Components\Struct\Configuration;

use Monolog\Logger;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class SignInWithKlarnaHelper implements SignInWithKlarnaHelperInterface
{
    protected ConfigReaderInterface $configReader;

    protected PluginService $pluginService;

    protected EntityRepository $customerRepository;

    protected Logger $logger;

    protected string $shopwareVersion;

    public function __construct(
        ConfigReaderInterface $configReader,
        PluginService $pluginService,
        EntityRepository $customerRepository,
        Logger $logger,
        string $shopwareVersion
    ) {
        $this->configReader = $configReader;
        $this->pluginService = $pluginService;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->shopwareVersion = $shopwareVersion;
    }

    public function requestCustomerAccessToken(RequestInterface $request, SalesChannelContext $salesChannelContext)
    {
        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        if ($configuration->get('debugMode')) {
            $this->logRequest($request, $configuration);
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);

        curl_setopt($curl, CURLOPT_POST, true);

        if (!empty($request->jsonSerialize())) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request));
        }

        curl_setopt($curl, CURLOPT_URL, $this->getEndpoint($request, $configuration));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($curl, CURLOPT_USERAGENT, $this->getUserAgent($request, $salesChannelContext->getContext()));

        /** @var string $rawResponse */
        $rawResponse = (string)curl_exec($curl);
        $curlStatus = curl_errno($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $data = json_decode($rawResponse, true);

        if (empty($data)) {
            $data = [];
        }

        if ($configuration->get('debugMode')) {
            $this->logResponse($request, $curlStatus, $httpStatus, $data);
        }

        $response = new GenericResponse();
        $response->assign([
            'httpStatus' => $httpStatus,
            'response' => $data,
        ]);

        return $response;
    }

    public function getCustomerByEmail(string $email, SalesChannelContext $salesChannelContext): ?CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('activeBillingAddress.country.states');
        $criteria->addAssociation('defaultBillingAddress.country.states');

        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('email', $email));
        // Don't allow guest-sign-in
        $criteria->addFilter(new EqualsFilter('guest', false));
        // Same email may be in multiple salesChannels
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelContext->getSalesChannelId()));

        return $this->customerRepository->search($criteria, $salesChannelContext->getContext())->first();
    }

    public function collectedAddressData(CustomerEntity $customer, RequestDataBag $customerData, SalesChannelContext $salesChannelContext)
    {
        $billingAddress = $customer->getActiveBillingAddress() ?: $customer->getDefaultBillingAddress();

        $activeBillingAddress = $this->collectShopwareBillingAddress($billingAddress);
        $matchedData = [];

        foreach ($customerData->get('billingAddress', []) as $key => $value) {
            if ($activeBillingAddress->has($key)) {
                $matchedData[$key] = [
                    'value' => $activeBillingAddress->get($key, null),
                    'klarnaValue' => $value,
                    'isIdentical' => ($activeBillingAddress->get($key, null) === $value)
                ];
            }
        }

        $billingAddress?->addArrayExtension("klarnaData", $matchedData);

        return $billingAddress;
    }

    public function addressDataIdentical(CustomerAddressEntity $customerAddressEntity): bool
    {
        if ($data = $customerAddressEntity->getExtension("klarnaData")) {
            foreach ($data as $value) {
                if (!isset($value["isIdentical"]) || !$value["isIdentical"]) {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

    private function getHeaders(): array
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'cache-control: no-cache',
        ];

        return $headers;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function logResponse(RequestInterface $request, int $curlStatus, int $httpStatus, array $data): void
    {
        $payload = [
            'curlStatus' => $curlStatus,
            'httpStatus' => $httpStatus,
            'response' => $data
        ];

        $this->logger->debug('Response: ' . $this->getCallType($request), $payload);
    }

    private function logRequest(RequestInterface $request, Configuration $configuration): void
    {
        $data = json_encode($request, JSON_PRESERVE_ZERO_FRACTION);

        if (!empty($data)) {
            $data = json_decode($data, true);
        } else {
            $data = [];
        }

        $payload = [
            'method' => $request->getMethod(),
            'endpoint' => $this->getEndpoint($request, $configuration),
            'request' => $data
        ];

        $this->logger->debug('Request: ' . $this->getCallType($request), $payload);
    }

    private function getCallType(RequestInterface $request): string
    {
        $array = explode('\\', get_class($request));

        return str_replace('Request', '', (string)end($array));
    }

    private function getEndpoint(
        RequestInterface $request,
        Configuration $configuration
    ): string {
        $testMode = $configuration->get('testMode', true);

        if ($testMode) {
            $baseUrl = 'https://login.playground.klarna.com';
        } else {
            $baseUrl = 'https://login.klarna.com';
        }

        return $baseUrl . $request->getEndpoint();
    }

    private function getUserAgent(RequestInterface $request, Context $context): string
    {
        $plugin = $this->pluginService->getPluginByName('KlarnaPayment', $context);

        $headers = [
            'Shopware/' . $this->shopwareVersion,
            'Licence/CE',
            'KlarnaPayment/' . $plugin->getVersion(),
            'CallType/' . $this->getCallType($request),
        ];

        return implode(' ', $headers);
    }

    private function collectShopwareBillingAddress(CustomerAddressEntity $address): RequestDataBag
    {
        $userBilling = new RequestDataBag();

        $userBilling->set('firstName', $address->getFirstName());
        $userBilling->set('lastName', $address->getLastName());

        $userBilling->set('street', $address->getStreet());
        $userBilling->set('zipcode', $address->getZipcode());
        $userBilling->set('city', $address->getCity());
        $userBilling->set('countryId', $address->getCountryId());
        $userBilling->set('phoneNumber', $address->getPhoneNumber());
        $userBilling->set('additionalAddressLine1', $address->getAdditionalAddressLine1());
        $userBilling->set('company', $address->getCompany());
        $userBilling->set('department', $address->getDepartment());
        $userBilling->set('title', $address->getTitle());

        return $userBilling;
    }
}
