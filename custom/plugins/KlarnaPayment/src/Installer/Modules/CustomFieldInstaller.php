<?php

declare(strict_types=1);

namespace KlarnaPayment\Installer\Modules;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use KlarnaPayment\Installer\InstallerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldInstaller implements InstallerInterface
{
    public const CUSTOMER_ENTITY_TYPE_LIMITED_COMPANY = 'LIMITED_COMPANY';
    public const CUSTOMER_ENTITY_TYPE_PUBLIC_LIMITED_COMPANY = 'PUBLIC_LIMITED_COMPANY';
    public const CUSTOMER_ENTITY_TYPE_ENTREPRENEURIAL_COMPANY = 'ENTREPRENEURIAL_COMPANY';
    public const CUSTOMER_ENTITY_TYPE_LIMITED_PARTNERSHIP_LIMITED_COMPANY = 'LIMITED_PARTNERSHIP_LIMITED_COMPANY';
    public const CUSTOMER_ENTITY_TYPE_LIMITED_PARTNERSHIP = 'LIMITED_PARTNERSHIP';
    public const CUSTOMER_ENTITY_TYPE_GENERAL_PARTNERSHIP = 'GENERAL_PARTNERSHIP';
    public const CUSTOMER_ENTITY_TYPE_REGISTERED_SOLE_TRADER = 'REGISTERED_SOLE_TRADER';
    public const CUSTOMER_ENTITY_TYPE_SOLE_TRADER = 'SOLE_TRADER';
    public const CUSTOMER_ENTITY_TYPE_CIVIL_LAW_PARTNERSHIP = 'CIVIL_LAW_PARTNERSHIP';
    public const CUSTOMER_ENTITY_TYPE_PUBLIC_INSTITUTION = 'PUBLIC_INSTITUTION';
    public const CUSTOMER_ENTITY_TYPE_OTHER = 'OTHER';

    public const CUSTOMER_ENTITY_TYPES = [
        self::CUSTOMER_ENTITY_TYPE_LIMITED_COMPANY,
        self::CUSTOMER_ENTITY_TYPE_PUBLIC_LIMITED_COMPANY,
        self::CUSTOMER_ENTITY_TYPE_ENTREPRENEURIAL_COMPANY,
        self::CUSTOMER_ENTITY_TYPE_LIMITED_PARTNERSHIP_LIMITED_COMPANY,
        self::CUSTOMER_ENTITY_TYPE_LIMITED_PARTNERSHIP,
        self::CUSTOMER_ENTITY_TYPE_GENERAL_PARTNERSHIP,
        self::CUSTOMER_ENTITY_TYPE_REGISTERED_SOLE_TRADER,
        self::CUSTOMER_ENTITY_TYPE_SOLE_TRADER,
        self::CUSTOMER_ENTITY_TYPE_CIVIL_LAW_PARTNERSHIP,
        self::CUSTOMER_ENTITY_TYPE_PUBLIC_INSTITUTION,
        self::CUSTOMER_ENTITY_TYPE_OTHER,
    ];

    public const FIELD_KLARNA_ORDER_ID = 'klarna_order_id';
    public const FIELD_KLARNA_FRAUD_STATUS = 'klarna_fraud_status';
    public const FIELD_KLARNA_ORDER_ADDRESS_HASH = 'klarna_order_address_hash';
    public const FIELD_KLARNA_ORDER_CART_HASH = 'klarna_order_cart_hash';
    public const FIELD_KLARNA_ORDER_CART_HASH_VERSION = 'klarna_order_cart_hash_version';
    public const FIELD_KLARNA_CUSTOMER_ENTITY_TYPE = 'klarna_customer_entity_type';
    public const FIELD_KLARNA_CUSTOMER_REGISTRATION_ID = 'klarna_customer_registration_id';

    public const FIELD_KLARNA_CUSTOMER_KLARNA_SIGN_IN = 'klarna_customer_klarna_sign_in';

    /**
     * Example:
     *
     * [
     *     'id'     => 'UUID',
     *     'name'   => 'field_set_technical_name',
     *     'active' => true,
     *     'config' => [
     *         'label' => [
     *             'en-GB' => 'Name',
     *             'de-DE' => 'Name',
     *         ],
     *     ],
     *     'customFields' => [
     *         [
     *             'id'     => 'UUID',
     *             'name'   => 'field_name',
     *             'active' => true,
     *             'type'   => CustomFieldTypes::TEXT,
     *             'config' => [
     *                 'label' => [
     *                     'en-GB' => 'Name',
     *                     'de-DE' => 'Name',
     *                 ],
     *             ],
     *         ],
     *     ],
     * ],
     */
    private const CUSTOM_FIELDSETS = [
        [
            'id' => '737401788e684947b085e4a6165f8457',
            'name' => 'klarna_order',
            'active' => true,
            'config' => [
                'label' => [
                    'en-GB' => 'Klarna Order',
                    'de-DE' => 'Klarna Bestellung',
                ],
            ],
            'customFields' => [
                [
                    'id' => 'b1ae547185a24e0d973724409232f7a9',
                    'name' => self::FIELD_KLARNA_ORDER_ID, // used for transactions
                    'active' => true,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Klarna Order ID',
                            'de-DE' => 'Klarna Order-ID',
                        ],
                    ],
                ],
                [
                    'id' => 'f585e86f340c4e31bdfd5ca49cc93d5f',
                    'name' => self::FIELD_KLARNA_FRAUD_STATUS, // used for transactions
                    'active' => true,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Klarna Fraud Status',
                            'de-DE' => 'Klarna Fraud-Status',
                        ],
                    ],
                ],
                [
                    'id' => '8477734532684639bd7b0fe8ea3da853',
                    'name' => self::FIELD_KLARNA_ORDER_ADDRESS_HASH, // used for orders
                    'active' => true,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Klarna Order Address Hash',
                            'de-DE' => 'Klarna Adress-Hash',
                        ],
                    ],
                ],
                [
                    'id' => 'c81828e18bbd44e7b0a59ce65e1f18a7',
                    'name' => self::FIELD_KLARNA_ORDER_CART_HASH, // used for orders
                    'active' => true,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Klarna Order Cart Hash',
                            'de-DE' => 'Klarna Warenkorb-Hash',
                        ],
                    ],
                ],
                [
                    'id' => '4dda308428b2446894fd51dded13b05f',
                    'name' => self::FIELD_KLARNA_ORDER_CART_HASH_VERSION, // used for orders
                    'active' => true,
                    'type' => CustomFieldTypes::INT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Klarna Order Cart Hash Version',
                            'de-DE' => 'Klarna Warenkorb-Hash Version',
                        ],
                    ],
                ],
            ],
        ],
        [
            'id' => 'bdf291e1e7be415b98ffb0bbc8eb710b',
            'name' => 'klarna_customer_address',
            'active' => true,
            'config' => [
                'label' => [
                    'en-GB' => 'Klarna Customer Address',
                    'de-DE' => 'Klarna Kundenaddresse',
                ],
            ],
            'relations' => [
                [
                    'id' => '33cedc98b32647979fd934874846f0cc',
                    'entityName' => 'customer_address',
                ],
            ],
            'customFields' => [
                [
                    'id' => 'aa00768cf7304a6e9739958559337dbd',
                    'name' => self::FIELD_KLARNA_CUSTOMER_ENTITY_TYPE,
                    'active' => true,
                    'allowCustomerWrite' => true,
                    'type' => CustomFieldTypes::SELECT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Legal form',
                            'de-DE' => 'Rechtsform',
                        ],
                        'componentName' => 'sw-single-select',
                        'options' => [
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_LIMITED_COMPANY,
                                'label' => [
                                    'en-GB' => 'Limited liability company',
                                    'de-DE' => 'Gesellschaft mit beschränkter Haftung (GmbH)',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_CIVIL_LAW_PARTNERSHIP,
                                'label' => [
                                    'en-GB' => 'Civil law partnership',
                                    'de-DE' => 'Gesellschaft bürgerlichen Rechts (GbR)',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_ENTREPRENEURIAL_COMPANY,
                                'label' => [
                                    'en-GB' => 'Entrepreneurial company (with limited liability)',
                                    'de-DE' => 'UG (haftungsbeschränkt)',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_GENERAL_PARTNERSHIP,
                                'label' => [
                                    'en-GB' => 'General partnership',
                                    'de-DE' => 'Offene Handelsgesellschaft (OHG)',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_LIMITED_PARTNERSHIP,
                                'label' => [
                                    'en-GB' => 'Limited partnership',
                                    'de-DE' => 'Kommanditgesellschaft (KG)',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_LIMITED_PARTNERSHIP_LIMITED_COMPANY,
                                'label' => [
                                    'en-GB' => 'Limited liability company and limited partnership',
                                    'de-DE' => 'Gesellschaft mit beschränkter Haftung und Kommanditgesellschaft (GmbH & KG)',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_PUBLIC_INSTITUTION,
                                'label' => [
                                    'en-GB' => 'Public institution',
                                    'de-DE' => 'Öffentliche Einrichtung',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_PUBLIC_LIMITED_COMPANY,
                                'label' => [
                                    'en-GB' => 'Public limited company',
                                    'de-DE' => 'Aktiengesellschaft (AG)',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_REGISTERED_SOLE_TRADER,
                                'label' => [
                                    'en-GB' => 'Registered sole trader',
                                    'de-DE' => 'Eingetragener Kaufmann/Kauffrau',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_SOLE_TRADER,
                                'label' => [
                                    'en-GB' => 'Sole trader',
                                    'de-DE' => 'Einzelunternehmen/Freiberuf',
                                ],
                            ],
                            [
                                'value' => self::CUSTOMER_ENTITY_TYPE_OTHER,
                                'label' => [
                                    'en-GB' => 'Other',
                                    'de-DE' => 'Sonstige',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => '82d6cdac3a8341b0aa7ebf31d1b1dfe0',
                    'name' => self::FIELD_KLARNA_CUSTOMER_REGISTRATION_ID,
                    'active' => true,
                    'allowCustomerWrite' => true,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Registration ID',
                            'de-DE' => 'Handelsregisternummer',
                        ],
                    ],
                ],
            ],
        ],
        [
            'id' => '019594de95de721d816de941da108b6c',
            'name' => 'klarna_customer',
            'active' => true,
            'config' => [
                'label' => [
                    'en-GB' => 'Klarna Customer',
                    'de-DE' => 'Klarna Kunde',
                ],
            ],
            'relations' => [
                [
                    'id' => '33cedc99932647979fd934874846f0cc',
                    'entityName' => 'customer',
                ],
            ],
            'customFields' => [
                [
                    'id' => '0195b347747b774e826c6ad569c63d45',
                    'name' => self::FIELD_KLARNA_CUSTOMER_KLARNA_SIGN_IN,
                    'active' => true,
                    'allowCustomerWrite' => true,
                    'type' => CustomFieldTypes::BOOL,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Klarna sign-in',
                            'de-DE' => 'Klarna Sign-in',
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * Hpp-Session custom fields
     */
    public const KLARNA_SESSION_KEY = 'klarnaHppSession';
    public const KLARNA_HPP_SESSION_ID = 'klarnaHppSessionId';
    public const KLARNA_SESSION_ID = 'klarnaSessionId';
    public const KLARNA_HPP_REDIRECT_SUCCESS = 'klarnaHppRedirctSuccessUrl';
    public const KLARNA_HPP_REDIRECT_ERROR = 'klarnaHppRedirctErrorUrl';
    public const KLARNA_HPP_SESSION_TOKEN = 'klarnaHppSessionToken';


    public function __construct(
        private readonly EntityRepository $customFieldSetRepository,
        private readonly EntityRepository $customFieldSetRelationRepository,
        private readonly Connection $connection
    ) {
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function install(InstallContext $context): void
    {
        $context->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context): void {
            $this->renameCustomFieldSets();

            $this->customFieldSetRepository->upsert(self::CUSTOM_FIELDSETS, $context);
        });
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function update(UpdateContext $context): void
    {
        $context->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context): void {
            $this->renameCustomFieldSets();

            $this->customFieldSetRepository->upsert(self::CUSTOM_FIELDSETS, $context);

            $criteria = new Criteria();
            $criteria
                ->addFilter(new EqualsFilter('customFieldSetId', 'bdf291e1e7be415b98ffb0bbc8eb710b'))
                ->addFilter(new EqualsFilter('entityName', 'order'));

            $ids = $this->customFieldSetRelationRepository->searchIds($criteria, $context)->getIds();

            if (!empty($ids)) {
                $ids = array_map(static function ($id) {
                    return ['id' => $id];
                }, $ids);

                $this->customFieldSetRelationRepository->delete($ids, $context);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        $context->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context): void {
            $data = $this->getDeactivateData();

            $this->customFieldSetRepository->upsert($data, $context);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
        $context->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context): void {
            $this->customFieldSetRepository->upsert(self::CUSTOM_FIELDSETS, $context);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
        $context->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context): void {
            $data = $this->getDeactivateData();

            $this->customFieldSetRepository->upsert($data, $context);
        });
    }

    /**
     * @return array|array[]
     */
    private function getDeactivateData(): array
    {
        $data = self::CUSTOM_FIELDSETS;

        foreach ($data as $setKey => $set) {
            $data[$setKey]['active'] = false;

            foreach ($set['customFields'] as $fieldKey => $customField) {
                $data[$setKey]['customFields'][$fieldKey]['active'] = false;
            }
        }

        return $data;
    }

    /**
     * Shopware does not allow to rename existing customFieldSets.
     * We're forced to change them by query.
     * @throws Exception
     */
    private function renameCustomFieldSets(): void
    {
        $this->connection->executeQuery('UPDATE `custom_field_set`
         SET `name` = CASE `id`
             WHEN 0x737401788e684947b085e4a6165f8457 THEN "klarna_order"
             WHEN 0xbdf291e1e7be415b98ffb0bbc8eb710b THEN "klarna_customer_address"
             WHEN 0x019594de95de721d816de941da108b6c THEN "klarna_customer"
         END
         WHERE `id` IN (0x737401788e684947b085e4a6165f8457, 0xbdf291e1e7be415b98ffb0bbc8eb710b, 0x019594de95de721d816de941da108b6c)');
    }
}
