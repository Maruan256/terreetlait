<?php declare(strict_types=1);

namespace KlarnaPayment\Storefront\Page\SiwkMatching;

use KlarnaPayment\Components\Controller\Storefront\SignInWithKlarnaController;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Shopware\Core\System\Country\SalesChannel\AbstractCountryRoute;
use Shopware\Core\System\Salutation\SalesChannel\AbstractSalutationRoute;
use Shopware\Core\System\Salutation\AbstractSalutationsSorter;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Page\MetaInformation;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class SiwkMatchingPageLoader
{
    private GenericPageLoaderInterface $genericPageLoader;

    private EventDispatcherInterface $eventDispatcher;

    private AbstractCountryRoute $countryRoute;

    private AbstractSalutationRoute $salutationRoute;

    private AbstractSalutationsSorter $salutationsSorter;

    public function __construct(
        GenericPageLoaderInterface $genericPageLoader,
        EventDispatcherInterface $eventDispatcher,
        AbstractCountryRoute $countryRoute,
        AbstractSalutationRoute $salutationRoute,
        AbstractSalutationsSorter $salutationsSorter
    ) {
        $this->genericPageLoader = $genericPageLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->countryRoute = $countryRoute;
        $this->salutationRoute = $salutationRoute;
        $this->salutationsSorter = $salutationsSorter;
    }

    public function load(Request $request, SalesChannelContext $salesChannelContext): SiwkMatchingPage
    {
        $page = $this->genericPageLoader->load($request, $salesChannelContext);
        $page = SiwkMatchingPage::createFrom($page);

        $this->setMetaInformation($page);

        $data = $request->getSession()->get(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_COLLECTED_DATA, []);
        $page->setCollectedData($data);
        $page->setCountries($this->getCountries($salesChannelContext));
        $page->setSalutations($this->getSalutations($salesChannelContext));
        $page->setIsRegisterAddress($request->getSession()->get(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_REGISTER_ADDRESS, false));

        $redirectRoute = $request->getSession()->get(SignInWithKlarnaController::SIGN_IN_WITH_KLARNA_MATCHING_REDIRECT, 'frontend.account.register.page');
        $page->setRedirectTo($redirectRoute);

        $this->eventDispatcher->dispatch(
            new SiwkMatchingPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    protected function setMetaInformation(SiwkMatchingPage $page): void
    {
        $page->getMetaInformation()?->setRobots('noindex,nofollow');

        if ($page->getMetaInformation() === null) {
            $page->setMetaInformation(new MetaInformation());
        }
    }

    private function getCountries(SalesChannelContext $salesChannelContext): CountryCollection
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('states');

        $countries = $this->countryRoute->load(new Request(), $criteria, $salesChannelContext)->getCountries();

        $countries->sortCountryAndStates();

        return $countries;
    }

    private function getSalutations(SalesChannelContext $salesChannelContext): SalutationCollection
    {
        $salutations = $this->salutationRoute->load(new Request(), $salesChannelContext, new Criteria())->getSalutations();

        return $this->salutationsSorter->sort($salutations);
    }
}