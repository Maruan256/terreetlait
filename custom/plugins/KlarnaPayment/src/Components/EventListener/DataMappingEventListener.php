<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Framework\Event\DataMappingEvent;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// TODO[KLARNASUPPORT-833]: remove this downwards compatibility for order_address custom field saving after 6.4.14.0
// tip: search for changes around the commit for this line or "KLARNASUPPORT-833" in the git history
class DataMappingEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::MAPPING_REGISTER_ADDRESS_BILLING  => 'addCustomFieldsToAddress',
            CustomerEvents::MAPPING_REGISTER_ADDRESS_SHIPPING => 'addCustomFieldsToAddress',
            CustomerEvents::MAPPING_ADDRESS_CREATE            => 'addCustomFieldsToAddress',
        ];
    }

    public function addCustomFieldsToAddress(DataMappingEvent $event): void
    {
        $input  = $event->getInput();
        $output = $event->getOutput();

        // Class will be available by the commit from 6.4.14.0 with the saving of custom fields
        // See https://github.com/shopware/platform/commit/c6d66b3c961b113285f895338aeeecd55b26a0ad
        if (class_exists('\Shopware\Core\System\SalesChannel\StoreApiCustomFieldMapper')) {
            return;
        }

        $requestCustomFields = $input->get('customFields');

        if (!$requestCustomFields instanceof RequestDataBag) {
            return;
        }

        $output['customFields'] = array_merge(
            $output['customFields'] ?? [],
            [
                CustomFieldInstaller::FIELD_KLARNA_CUSTOMER_ENTITY_TYPE     => $requestCustomFields->get(CustomFieldInstaller::FIELD_KLARNA_CUSTOMER_ENTITY_TYPE),
                CustomFieldInstaller::FIELD_KLARNA_CUSTOMER_REGISTRATION_ID => $requestCustomFields->get(CustomFieldInstaller::FIELD_KLARNA_CUSTOMER_REGISTRATION_ID),
            ]
        );

        $event->setOutput($output);
    }
}
