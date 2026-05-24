<?php declare(strict_types=1);

namespace KlarnaPayment\Storefront\Controller;

use Exception;
use KlarnaPayment\Components\Controller\Storefront\SignInWithKlarnaController;
use KlarnaPayment\Storefront\Page\SiwkMatching\SiwkMatchingPageLoader;

use Monolog\Logger;
use Shopware\Core\Checkout\Customer\Exception\BadCredentialsException;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\Validation\Constraint\CustomerZipCode;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidationFactoryInterface;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Storefront\Controller\Exception\StorefrontException;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Storefront\Checkout\Cart\SalesChannel\StorefrontCartFacade;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\Checkout\Customer\SalesChannel\UpsertAddressRoute;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Context;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Length;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class SiwkMatchingController extends StorefrontController
{
    public const KLARNA_MATCHING_SUBMIT_UPDATE = 'update';
    public const KLARNA_MATCHING_SUBMIT_CREATE = 'create';

    protected SiwkMatchingPageLoader $siwkMatchingPageLoader;

    protected UpsertAddressRoute $upsertAddressRoute;

    protected AccountService $accountService;

    protected StorefrontCartFacade $cartFacade;

    protected EntityRepository $customerRepository;

    protected EntityRepository $salutationRepository;

    protected EntityRepository $customerAddressRepository;

    protected Logger $logger;

    protected DataValidationFactoryInterface $addressValidationFactory;

    protected DataValidator $validator;

    protected AbstractRegisterRoute $registerRoute;

    public function __construct(
        SiwkMatchingPageLoader $siwkMatchingPageLoader,
        UpsertAddressRoute $upsertAddressRoute,
        AccountService $accountService,
        StorefrontCartFacade $cartFacade,
        EntityRepository $customerRepository,
        EntityRepository $salutationRepository,
        EntityRepository $customerAddressRepository,
        Logger $logger,
        DataValidator $validator,
        DataValidationFactoryInterface $addressValidationFactory,
        AbstractRegisterRoute $registerRoute
    ) {
        $this->siwkMatchingPageLoader = $siwkMatchingPageLoader;
        $this->upsertAddressRoute = $upsertAddressRoute;
        $this->accountService = $accountService;
        $this->cartFacade = $cartFacade;
        $this->customerRepository = $customerRepository;
        $this->salutationRepository = $salutationRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->addressValidationFactory = $addressValidationFactory;
        $this->registerRoute = $registerRoute;
    }

    /**
     * @throws StorefrontException
     */
    #[Route(path: '/siwk-matching', name: 'frontend.klarna.siwk-matching.page', methods: ['GET'])]
    public function matchingPage(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        if ($request->getSession()->get(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_COLLECTED_DATA)) {
            $page = $this->siwkMatchingPageLoader->load($request, $salesChannelContext);

            return $this->renderStorefront('@KlarnaPayment/storefront/page/klarna/siwk-matching/index.html.twig', [
                'page' => $page
            ]);
        }

        return $this->redirectToRoute("frontend.account.login");
    }

    /**
     * @param Request $request ,
     * @param RequestDataBag $data ,
     * @param SalesChannelContext $salesChannelContext
     *
     * @return Response
     * @throws BadCredentialsException
     * @throws CustomerNotFoundByIdException
     * @throws RoutingException
     * @throws StorefrontException
     */
    #[Route(path: '/handle/siwk-matching', name: 'frontend.klarna.handle.siwk-matching', methods: ['POST'])]
    public function handleMatching(Request $request, RequestDataBag $data, SalesChannelContext $salesChannelContext): Response
    {
        $submitType = $data->get('submitButton');
        $customerId = $data->get('customerId');
        $address = new RequestDataBag([...$data->get('siwkAddress', []), ...$data->get('billingAddress', [])]);
        $redirectRoute = $data->get('redirectTo', 'frontend.account.login.page');
        $errorRoute = $data->get('errorRoute', 'frontend.klarna.siwk-matching.page');
        $isAddressRegister = $request->getSession()->get(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_REGISTER_ADDRESS, false);

        $this->logger->info('Sign-in with Klarna handle matching', ['requestData' => $data->all()]);

        $customer = $customerId ? $this->getCustomerById($customerId, $salesChannelContext->getContext()) : null;

        if ($isAddressRegister || ($customer && $address->count())) {
            try {
                // Update the already created customer
                if ($submitType === self::KLARNA_MATCHING_SUBMIT_UPDATE && !$isAddressRegister) {
                    $this->validateData($data, $salesChannelContext);

                    $this->overwriteBillingAddress($address, $customerId, $salesChannelContext->getContext());
                } elseif ($submitType === self::KLARNA_MATCHING_SUBMIT_CREATE && $isAddressRegister) {
                    // Register the customer in case we didn't receive any addresses from SIWK
                    $this->validateData($data, $salesChannelContext);

                    /** @var RequestDataBag $siwkData
                     * Merge data received from SWIK with the filled out data from the customer
                     * */
                    $siwkData = $request->getSession()->get(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_COLLECTED_DATA);
                    $siwkData->remove("firstName");
                    $siwkData->remove("lastName");
                    $address->remove("id");

                    $customerData = new RequestDataBag($siwkData->all());
                    $customerData->set("billingAddress", $address);
                    $customerData->set("acceptedDataProtection", true);

                    $this->registerCustomer($customerData, $salesChannelContext);
                }

            } catch (ConstraintViolationException $formViolations) {
                $this->logger->error('Sign-in with Klarna handle matching upsert', [
                    'formViolations' => $formViolations
                ]);

                if (empty($errorRoute)) {
                    throw RoutingException::missingRequestParameter('errorRoute');
                }

                $params = $this->decodeParam($request, 'errorParameters');

                $request->getSession()->remove(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_COLLECTED_DATA);
                $request->getSession()->remove(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_REGISTER_ADDRESS);

                return $this->forwardToRoute($errorRoute, ['formViolations' => $formViolations], $params);
            }
        }

        $isLoggedIn = $this->loginCustomer($customer, $salesChannelContext);

        // Remove, otherwise user can visit SIWK page again
        $request->getSession()->remove(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_COLLECTED_DATA);
        $request->getSession()->remove(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_REGISTER_ADDRESS);

        return $this->redirectToRoute($isLoggedIn ? $redirectRoute : 'frontend.account.register.page');
    }

    /**
     * @throws CustomerNotFoundByIdException
     * @throws BadCredentialsException
     */
    private function loginCustomer(?CustomerEntity $customer, SalesChannelContext $salesChannelContext): bool
    {
        if ($customer === null || !$customer->getActive()) {
            return false;
        }

        $token = $this->accountService->loginById($customer->getId(), $salesChannelContext);

        $cartBeforeNewContext = $this->cartFacade->get($token, $salesChannelContext);

        if (!empty($token)) {
            $this->addCartErrors($cartBeforeNewContext);
        }

        return true;
    }

    private function registerCustomer(RequestDataBag $customerData, SalesChannelContext $salesChannelContext): void
    {
        try {
            $this->registerRoute->register(
                $customerData,
                $salesChannelContext,
                false
            );
        } catch (Exception $e) {
            $this->logger->error('Could not register customer for Sign in with Klarna.', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function getCustomerById(string $customerId, Context $context): ?CustomerEntity
    {
        return $this->customerRepository->search(new Criteria([$customerId]), $context)->first();
    }

    private function overwriteBillingAddress(RequestDataBag $addressData, string $customerId, Context $context): void
    {
        $this->customerAddressRepository->update([
            [
                "id" => $addressData->get("id"),
                "customerId" => $customerId,
                "salutationId" => $addressData->get("salutationId"),
                "firstName" => $addressData->get("firstName"),
                "lastName" => $addressData->get("lastName"),
                "street" => $addressData->get("street"),
                "zipcode" => $addressData->get("zipcode"),
                "city" => $addressData->get("city"),
                "additionalAddressLine1" => $addressData->get("additionalAddressLine1"),
                "countryId" => $addressData->get("countryId"),
                "phoneNumber" => $addressData->get("phoneNumber"),
            ]
        ], $context);
    }

    /**
     * @throws ConstraintViolationException
     */
    private function validateData(RequestDataBag $data, SalesChannelContext $salesChannelContext): void
    {
        $definition = new DataValidationDefinition('siwk.validate');
        $definition->addSub('siwkAddress', $this->createAddressValidationDefinition($data->get("siwkAddress")->get("countryId"), $salesChannelContext));

        $violations = $this->validator->getViolations($data->all(), $definition);

        if (!$violations->count()) {
            return;
        }

        throw new ConstraintViolationException($violations, $data->all());
    }

    private function createAddressValidationDefinition(string $countryId, SalesChannelContext $salesChannelContext): DataValidationDefinition
    {
        $validation = $this->addressValidationFactory->create($salesChannelContext);

        $validation->set('zipcode', new CustomerZipCode(['countryId' => $countryId]));
        $validation->add('zipcode', new Length(['max' => 50]));

        return $validation;
    }
}