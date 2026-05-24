<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Converter;

use KlarnaPayment\Components\ConfigReader\ConfigReader;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CustomerAddressConverter
{
    /** @var EntityRepository */
    private $countryRepository;

    /** @var ConfigReader */
    private $configReader;

    /** @var SystemConfigService */
    private $systemConfigService;


    public function __construct(EntityRepository $countryRepository, ConfigReader $configReader, SystemConfigService $systemConfigService)
    {
        $this->countryRepository = $countryRepository;
        $this->configReader      = $configReader;
        $this->systemConfigService = $systemConfigService;
    }

    public function convertToRegisterRequestDataBag(array $klarnaAddress, SalesChannelContext $context): RequestDataBag
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());
        $country       = $this->fetchCountry($klarnaAddress['country'], $context);

        $salesChannelId = $context->getSalesChannel()->getId();

        $customerRegisterAddress = new RequestDataBag();
        $customerRegisterAddress->set('salutationId', $configuration->get('klarnaExpressDefaultSalutation', null));
        $customerRegisterAddress->set('firstName', $klarnaAddress['given_name']);
        $customerRegisterAddress->set('lastName', $klarnaAddress['family_name']);
        $customerRegisterAddress->set('street', $klarnaAddress['street_address']);
        $customerRegisterAddress->set('zipcode', $klarnaAddress['postal_code']);
        $customerRegisterAddress->set('city', $klarnaAddress['city']);
        $customerRegisterAddress->set('countryId', $country->getId());

        if(isset($klarnaAddress['phone']) && $this->systemConfigService->getBool('core.loginRegistration.phoneNumberFieldRequired', $salesChannelId)){
            $customerRegisterAddress->set('phoneNumber', $klarnaAddress['phone']);
        }

        if(isset($klarnaAddress['street_address2']) && $this->systemConfigService->getBool('core.loginRegistration.additionalAddressField1Required', $salesChannelId)){
            $customerRegisterAddress->set('additionalAddressLine1', $klarnaAddress['street_address2']);
        }

        return $customerRegisterAddress;
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
}
