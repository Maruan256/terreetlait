<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Storefront;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Helper\SignInWithKlarnaHelper\SignInWithKlarnaHelperInterface;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;

use Monolog\Logger;
use Shopware\Core\Checkout\Customer\Exception\BadCredentialsException;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Framework\Util\Random;
use Shopware\Storefront\Checkout\Cart\SalesChannel\StorefrontCartFacade;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Routing\Router;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @RouteScope(scopes={"storefront"})
 * @Route(defaults={"_routeScope": {"storefront"}})
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class SignInWithKlarnaController extends StorefrontController
{
    public const SIGN_IN_WITH_KLARNA_SESSION_KEY = 'signInWithKlarna';
    public const SIGN_IN_WITH_KLARNA_COLLECTED_DATA = 'signInWithKlarnaMatchedData';
    public const SIGN_IN_WITH_KLARNA_MATCHING_REDIRECT = 'signInWithKlarnaMatchingRedirect';
    public const SIGN_IN_WITH_KLARNA_REGISTER_ADDRESS = 'signInWithKlarnaRegisterAddress';

    protected AbstractRegisterRoute $registerRoute;

    protected ConfigReaderInterface $configReader;

    protected SystemConfigService $systemConfigService;

    protected EntityRepository $countryRepository;

    protected EntityRepository $customerRepository;

    protected AccountService $accountService;

    protected StorefrontCartFacade $cartFacade;

    protected SignInWithKlarnaHelperInterface $signInWithKlarnaHelper;

    protected Router $router;

    protected Logger $logger;

    public function __construct(
        AbstractRegisterRoute $registerRoute,
        ConfigReaderInterface $configReader,
        SystemConfigService $systemConfigService,
        EntityRepository $countryRepository,
        EntityRepository $customerRepository,
        AccountService $accountService,
        StorefrontCartFacade $cartFacade,
        SignInWithKlarnaHelperInterface $signInWithKlarnaHelper,
        Router $router,
        Logger $logger
    ) {
        $this->registerRoute = $registerRoute;
        $this->configReader = $configReader;
        $this->systemConfigService = $systemConfigService;
        $this->countryRepository = $countryRepository;
        $this->customerRepository = $customerRepository;
        $this->accountService = $accountService;
        $this->cartFacade = $cartFacade;
        $this->signInWithKlarnaHelper = $signInWithKlarnaHelper;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * @Route("/klarna/sign-in/callback", defaults={"XmlHttpRequest": true}, name="widgets.klarna.sign-in.callback", methods={"POST"})
     */
    #[Route(path: '/klarna/sign-in/callback', name: 'widgets.klarna.sign-in.callback', methods: ['POST'], defaults: ['XmlHttpRequest' => true])]
    public function signInCallback(Request $request, RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $this->logger->info('Sign-in with Klarna start', ['userData' => $data->all(), 'request' => $request]);

        $loggedIn = false;

        $klarnaCustomerData = $data->get('signinResponse');
        $redirectRoute = $data->get('redirectRoute');
        $errorRoute = $data->get('errorRoute');

        $customerData = $this->getCustomerRegisterData($klarnaCustomerData, $salesChannelContext);

        if ($customerData->all()) {
            $userAccountLinking = $klarnaCustomerData->get('user_account_linking');
            // We may receive no billing address, depending on the customer actions in the SIWK popup
            $hasBillingAddress = $customerData->has("billingAddress") && $customerData->get("billingAddress") instanceof RequestDataBag;

            $customer = $this->signInWithKlarnaHelper->getCustomerByEmail($customerData->get('email'), $salesChannelContext);

            // Simply register the customer if not already in the shop and if we received the customer address
            if (!$customer && $hasBillingAddress) {
                $loggedIn = $this->registerCustomer($customerData, $salesChannelContext);
                $request->getSession()->set(self::SIGN_IN_WITH_KLARNA_SESSION_KEY, $userAccountLinking->get('user_account_linking_refresh_token'));

                return new JsonResponse([
                    'success' => $loggedIn,
                    'redirectUrl' => $this->router->generate(
                        $loggedIn ? $redirectRoute : $errorRoute,
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ]);
            }

            /**
             * If the customer is not in the shop and there is no billing address,
             * redirect them to the matching page for manual registration
             */
            if (!$customer && !$hasBillingAddress) {
                $request->getSession()->set(self::SIGN_IN_WITH_KLARNA_REGISTER_ADDRESS, true);
                $request->getSession()->set(self::SIGN_IN_WITH_KLARNA_MATCHING_REDIRECT, $redirectRoute);
                $request->getSession()->set(self::SIGN_IN_WITH_KLARNA_COLLECTED_DATA, $customerData);

                return new JsonResponse([
                    'success' => true,
                    'redirectUrl' => $this->router->generate(
                        'frontend.klarna.siwk-matching.page',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ]);
            }

            $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
            $matchedData = $this->signInWithKlarnaHelper->collectedAddressData($customer, $customerData, $salesChannelContext);

            // Address matching
            if ($configuration->get('signInWithKlarnaAllowMatching') && !$this->signInWithKlarnaHelper->addressDataIdentical($matchedData)) {
                $request->getSession()->set(self::SIGN_IN_WITH_KLARNA_COLLECTED_DATA, $matchedData);
                $request->getSession()->set(self::SIGN_IN_WITH_KLARNA_MATCHING_REDIRECT, $redirectRoute);

                return new JsonResponse([
                    'success' => true,
                    'redirectUrl' => $this->router->generate(
                        'frontend.klarna.siwk-matching.page',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ]);
            }

            // We sign in if the customer already exists and/or the address is identical
            $loggedIn = $this->loginCustomer($customer, $salesChannelContext);
        }

        return new JsonResponse([
            'success' => $loggedIn,
            'redirectUrl' => $this->router->generate(
                $loggedIn ? $redirectRoute : $errorRoute,
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        ]);
    }

    private function registerCustomer(RequestDataBag $customerData, SalesChannelContext $salesChannelContext): bool
    {
        try {
            $this->registerRoute->register(
                $customerData,
                $salesChannelContext,
                false
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Could not login customer for Sign in with Klarna.', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return false;
        }
    }

    private function getCustomerRegisterData(RequestDataBag $data, SalesChannelContext $salesChannelContext): RequestDataBag
    {
        $user = new RequestDataBag();

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $userAccountProfile = $data->get('user_account_profile');

        if ($userAccountProfile->get('billing_address') instanceof RequestDataBag) {
            $billing = $userAccountProfile->get('billing_address');
            $country = $this->fetchCountry($billing->get('country'), $salesChannelContext);

            $userBilling = new RequestDataBag();

            $userBilling->set('firstName', $userAccountProfile->get('given_name'));
            $userBilling->set('lastName', $userAccountProfile->get('family_name'));

            $userBilling->set('street', $billing->get('street_address'));
            $userBilling->set('zipcode', $billing->get('postal_code'));
            $userBilling->set('city', $billing->get('city'));
            $userBilling->set('countryId', $country->getId());
            $userBilling->set('phoneNumber', $userAccountProfile->get('phone', null));

            $additionalAddress = $billing->get('street_address_2', null) ?: $billing->get('street_address2', null);

            $userBilling->set('additionalAddressLine1', $additionalAddress);

            $userBilling->set('company', null);
            $userBilling->set('department', null);
            $userBilling->set('title', null);

            if ($userAccountProfile->get('date_of_birth', false)) {
                $parts = explode('-', $userAccountProfile->get('date_of_birth'));
                if (count($parts) === 3) {
                    $user->set('birthdayDay', $parts[2]);
                    $user->set('birthdayMonth', $parts[1]);
                    $user->set('birthdayYear', $parts[0]);
                }
            }

            $user->set('billingAddress', $userBilling);
        }

        $user->set('email', $userAccountProfile->get('email'));
        $user->set('firstName', $userAccountProfile->get('given_name'));
        $user->set('lastName', $userAccountProfile->get('family_name'));
        $user->set('guest', false);
        $user->set('createCustomerAccount', true);
        $user->set('password', Random::getAlphanumericString(32));
        $user->set('salesChannelId', $salesChannelId);
        $user->set('boundSalesChannelId', $salesChannelId);
        $user->set('acceptedDataProtection', true);

        $customFields = new RequestDataBag();
        $customFields->set(CustomFieldInstaller::FIELD_KLARNA_CUSTOMER_KLARNA_SIGN_IN, true);

        $user->set('customFields', $customFields);

        return $user;
    }

    private function fetchCountry(string $region, SalesChannelContext $context): CountryEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $region));

        /** @var null|CountryEntity $country */
        $country = $this->countryRepository->search($criteria, $context->getContext())->first();

        if ($country === null) {
            return $context->getShippingLocation()->getCountry();
        }

        return $country;
    }

    private function loginCustomer(CustomerEntity $customer, SalesChannelContext $salesChannelContext): bool
    {
        try {
            if (!$customer->getActive()) {
                return false;
            }

            $token = $this->accountService->loginById($customer->getId(), $salesChannelContext);

            $cartBeforeNewContext = $this->cartFacade->get($token, $salesChannelContext);

            if (!empty($token)) {
                $this->addCartErrors($cartBeforeNewContext);
            }

            return true;
        } catch (CustomerNotFoundByIdException|BadCredentialsException $exception) {
            $this->logger->info('Sign-in with Klarna - login error', ['customerId' => $customer->getId(), 'error' => $exception->getMessage()]);

            return false;
        }
    }
}
