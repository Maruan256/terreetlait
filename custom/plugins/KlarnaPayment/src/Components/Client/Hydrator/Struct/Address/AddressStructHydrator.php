<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\Address;

use KlarnaPayment\Components\Client\Struct\Address;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Country\Exception\CountryNotFoundException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AddressStructHydrator implements AddressStructHydratorInterface
{
    /** @var EntityRepository */
    private $countryRepository;

    /** @var string[] */
    private $countryIds = [];

    /** @var CountryEntity[] */
    private $countries;

    public function __construct(
        EntityRepository $countryRepository
    ) {
        $this->countryRepository    = $countryRepository;
    }

    public function hydrateFromContext(SalesChannelContext $context, string $type = self::TYPE_BILLING): ?Address
    {
        $customer = $context->getCustomer();

        if ($customer === null) {
            return null;
        }

        if ($type === self::TYPE_BILLING) {
            $customerAddress = $customer->getActiveBillingAddress();
        } elseif ($type === self::TYPE_SHIPPING) {
            $customerAddress = $customer->getActiveShippingAddress();
        } else {
            throw new \LogicException('unsupported customer address type');
        }

        if ($customerAddress === null) {
            return null;
        }

        $address = new Address();
        $address->assign([
            'companyName'    => $customerAddress->getCompany(),
            'firstName'      => $customerAddress->getFirstName(),
            'lastName'       => $customerAddress->getLastName(),
            'postalCode'     => $customerAddress->getZipcode(),
            'streetAddress'  => $customerAddress->getStreet(),
            'streetAddress2' => $this->getStreetAddress2($customerAddress->getAdditionalAddressLine1(), $customerAddress->getAdditionalAddressLine2()),
            'city'           => $customerAddress->getCity(),
            'country'        => $this->getCountry($customerAddress->getCountryId(), $context->getContext())->getIso(),
            'region'         => $customerAddress->getCountryState() instanceof CountryStateEntity ? $customerAddress->getCountryState()->getShortCode() : null,
            'email'          => $customer->getEmail(),
            'phoneNumber'    => $customerAddress->getPhoneNumber(),
        ]);

        return $address;
    }

    public function hydrateFromOrderAddress(?OrderAddressEntity $address, ?OrderCustomerEntity $customer): ?Address
    {
        if (!$address || !$customer) {
            throw new \LogicException('Address or customer missing');
        }

        $addressStruct = new Address();
        $addressStruct->assign([
            'companyName'    => $address->getCompany(),
            'firstName'      => $address->getFirstName(),
            'lastName'       => $address->getLastName(),
            'postalCode'     => $address->getZipcode(),
            'streetAddress'  => $address->getStreet(),
            'streetAddress2' => $this->getStreetAddress2($address->getAdditionalAddressLine1(), $address->getAdditionalAddressLine2()),
            'city'           => $address->getCity(),
            'country'        => $address->getCountry() instanceof CountryEntity ? $address->getCountry()->getIso() : '',
            'region'         => $address->getCountryState() instanceof CountryStateEntity ? $address->getCountryState()->getShortCode() : null,
            'email'          => $customer->getEmail(),
            'phoneNumber'    => $address->getPhoneNumber() ?? 'No number provided',
        ]);

        return $addressStruct;
    }

    public function hydrateFromResponse(array $address, Context $context): Address
    {
        $addressStruct = new Address();
        $addressStruct->assign([
            'companyName'    => $address['organization_name'] ?? '',
            'firstName'      => $address['given_name'],
            'lastName'       => $address['family_name'],
            'postalCode'     => $address['postal_code'],
            'streetAddress'  => $address['street_address'],
            'streetAddress2' => $address['street_address2'],
            'city'           => $address['city'],
            'country'        => $this->getCountryId($address['country'], $context),
            'email'          => $address['email'],
            'phoneNumber'    => $address['phone'],
        ]);

        return $addressStruct;
    }

    private function getStreetAddress2(?string $line1, ?string $line2): string
    {
        $streetAddress2 = (string) $line1;

        if (!empty($line2)) {
            $streetAddress2 .= ' - ' . $line2;
        }

        return $streetAddress2;
    }

    private function getCountryId(string $iso2, Context $context): string
    {
        if (isset($this->countryIds[$iso2])) {
            return $this->countryIds[$iso2];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso2));

        $countryId = $this->countryRepository->searchIds($criteria, $context)->firstId();

        if ($countryId === null) {
            throw new CountryNotFoundException($iso2);
        }

        $this->countryIds[$iso2] = $countryId;

        return $countryId;
    }

    private function getCountry(string $countryId, Context $context): CountryEntity
    {
        if (isset($this->countries[$countryId])) {
            return $this->countries[$countryId];
        }

        $criteria = new Criteria([$countryId]);

        /** @var null|CountryEntity $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        if ($country === null) {
            throw new \LogicException('missing order customer country');
        }

        $this->countries[$countryId] = $country;

        return $country;
    }
}
