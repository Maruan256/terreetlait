<?php declare(strict_types=1);

namespace KlarnaPayment\Storefront\Page\SiwkMatching;

use Shopware\Storefront\Page\Page;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Salutation\SalutationCollection;

class SiwkMatchingPage extends Page
{
    protected $collectedData;

    protected CountryCollection $countries;

    protected SalutationCollection $salutations;

    protected string $redirectTo;

    protected array $redirectParameters;

    protected bool $isRegisterAddress;

    public function isRegisterAddress(): bool
    {
        return $this->isRegisterAddress;
    }

    public function setIsRegisterAddress(bool $isRegisterAddress): void
    {
        $this->isRegisterAddress = $isRegisterAddress;
    }

    public function getCollectedData()
    {
        return $this->collectedData;
    }

    public function setCollectedData($collectedData): void
    {
        $this->collectedData = $collectedData;
    }

    public function getCountries(): CountryCollection
    {
        return $this->countries;
    }

    public function setCountries(CountryCollection $countries): void
    {
        $this->countries = $countries;
    }

    public function getSalutations(): SalutationCollection
    {
        return $this->salutations;
    }

    public function setSalutations(SalutationCollection $salutations): void
    {
        $this->salutations = $salutations;
    }

    public function getRedirectTo(): string
    {
        return $this->redirectTo;
    }

    public function setRedirectTo(string $redirectTo): void
    {
        $this->redirectTo = $redirectTo;
    }

    public function getRedirectParameters(): array
    {
        return $this->redirectParameters;
    }

    public function setRedirectParameters(array $redirectParameters): void
    {
        $this->redirectParameters = $redirectParameters;
    }
}