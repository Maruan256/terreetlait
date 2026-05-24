<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\Customer;

use KlarnaPayment\Components\Client\Struct\Customer;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Components\Helper\SignInWithKlarnaHelper\SignInWithKlarnaHelperInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\RefreshToken\RefreshTokenRequestHydratorInterface;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Controller\Storefront\SignInWithKlarnaController;
use KlarnaPayment\Components\Client\Response\GenericResponse;

use Monolog\Logger;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class CustomerStructHydrator implements CustomerStructHydratorInterface
{
    public const TYPE_PERSON       = 'person';
    public const TYPE_ORGANIZATION = 'organization';

    /** @var PaymentHelperInterface */
    private $paymentHelper;

    /** @var SignInWithKlarnaHelperInterface */
    private $signInWithKlarnaHelper;

    /** @var RefreshTokenRequestHydratorInterface */
    private $refreshTokenRequestHydrator;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var Logger */
    private $logger;

    /** RequestStack */
    private $requestStack;

    public function __construct(
        PaymentHelperInterface $paymentHelper,
        SignInWithKlarnaHelperInterface $signInWithKlarnaHelper,
        RefreshTokenRequestHydratorInterface $refreshTokenRequestHydrator,
        ConfigReaderInterface $configReader,
        Logger $logger,
        RequestStack $requestStack
    )
    {
        $this->paymentHelper = $paymentHelper;
        $this->signInWithKlarnaHelper = $signInWithKlarnaHelper;
        $this->refreshTokenRequestHydrator = $refreshTokenRequestHydrator;
        $this->configReader = $configReader;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    public function hydrate(SalesChannelContext $salesChannelContext): ?Customer
    {
        $contextCustomer = $salesChannelContext->getCustomer();

        if ($contextCustomer === null) {
            return null;
        }

        $customer = new Customer();

        if ($this->paymentHelper->isKlarnaPaymentsEnabled($salesChannelContext) && $contextCustomer->getBirthday() !== null) {
            $customer->assign([
                'birthday' => $contextCustomer->getBirthday(),
            ]);
        }

        $configuration  = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if($session &&
            ($refreshToken = $session->get(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_SESSION_KEY, null)) &&
            $configuration->get('isSignInWithKlarnaActive') &&
            $clientKey = $configuration->get('signInWithKlarnaClientKey')
        ){
            $dataBag = new RequestDataBag();
            $dataBag->add([
                'client_id' => $clientKey,
                'refresh_token' => $refreshToken
            ]);

            $request = $this->refreshTokenRequestHydrator->hydrate($dataBag);
            $response = $this->signInWithKlarnaHelper->requestCustomerAccessToken($request, $salesChannelContext);

            if($this->isValidResponseStatus($response)){
                $data = $response->getResponse();
                $customer->assign([
                    'klarnaAccessToken' => $data['access_token']
                ]);
                $this->requestStack->getSession()->set(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_SESSION_KEY, $data['refresh_token']);
            }
        }

        $billingAddress = $this->getBillingAddress($contextCustomer);

        if ($billingAddress === null) {
            return $customer;
        }

        $type = !empty($billingAddress->getCompany())
            ? self::TYPE_ORGANIZATION
            : self::TYPE_PERSON;

        $customer->assign([
            'type' => $type,
        ]);

        if (!$this->paymentHelper->isKlarnaPaymentsSelected($salesChannelContext)) {
            return $customer;
        }

        $customer->assign([
            'vatId' => $this->getVatId($billingAddress, $contextCustomer),
            'title' => $billingAddress->getTitle(),
        ]);

        if ($type === self::TYPE_ORGANIZATION) {
            $customFields = $billingAddress->getCustomFields();

            if ($customFields === null || !isset($customFields[CustomFieldInstaller::FIELD_KLARNA_CUSTOMER_ENTITY_TYPE], $customFields[CustomFieldInstaller::FIELD_KLARNA_CUSTOMER_REGISTRATION_ID])) {
                return $customer;
            }

            $customer->assign([
                'organizationEntityType'     => $customFields[CustomFieldInstaller::FIELD_KLARNA_CUSTOMER_ENTITY_TYPE],
                'organizationRegistrationId' => $customFields[CustomFieldInstaller::FIELD_KLARNA_CUSTOMER_REGISTRATION_ID],
            ]);
        }

        return $customer;
    }

    private function getBillingAddress(CustomerEntity $contextCustomer): ?CustomerAddressEntity
    {
        $billingAddress = $contextCustomer->getActiveBillingAddress();

        if ($billingAddress === null) {
            return null;
        }

        return $billingAddress;
    }

    private function getVatId(CustomerAddressEntity $billingAddress, CustomerEntity $customer): ?string
    {
        // Backwards compatibility for Shopware < 6.3.5.0
        if (method_exists($billingAddress, 'getVatId')) {
            return $billingAddress->getVatId();
        }

        /** @phpstan-ignore-next-line */
        $vatIds = $customer->getVatIds();

        if ($vatIds === null) {
            return null;
        }

        return array_shift($vatIds);
    }

    private function isValidResponseStatus(GenericResponse $response): bool
    {
        return in_array($response->getHttpStatus(), [Response::HTTP_OK, Response::HTTP_NO_CONTENT], true);
    }
}
