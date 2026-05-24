<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Struct;

use Shopware\Core\Framework\Struct\Struct;

class Address extends Struct
{
    /** @var null|string */
    protected $companyName;

    /** @var string */
    protected $firstName;

    /** @var string */
    protected $lastName;

    /** @var string */
    protected $postalCode;

    /** @var string */
    protected $streetAddress;

    /** @var string */
    protected $streetAddress2;

    /** @var string */
    protected $city;

    /** @var null|string */
    protected $region;

    /** @var string */
    protected $country;

    /** @var string */
    protected $email;

    /** @var null|string */
    protected $phoneNumber;

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getPostalCode(): string|null
    {
        return $this->postalCode;
    }

    public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }

    public function getStreetAddress2(): ?string
    {
        return $this->streetAddress2;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function jsonSerialize(): array
    {
        return [
            'city'              => $this->getCity(),
            'country'           => $this->getCountry(),
            'email'             => $this->getEmail(),
            'family_name'       => $this->getLastName(),
            'given_name'        => $this->getFirstName(),
            'organization_name' => $this->getCompanyName(),
            'phone'             => $this->getPhoneNumber(),
            'postal_code'       => $this->getPostalCode(),
            'region'            => $this->getRegion(),
            'street_address'    => $this->getStreetAddress(),
            'street_address2'   => $this->getStreetAddress2()
        ];
    }
}
