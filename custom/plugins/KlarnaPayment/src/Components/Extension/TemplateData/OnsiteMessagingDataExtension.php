<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Extension\TemplateData;

use Shopware\Core\Framework\Struct\Struct;

class OnsiteMessagingDataExtension extends Struct
{
    public const KlARNA_MERCHANT_SCRIPT_URL = 'https://eu-library.klarnaservices.com/merchant.js';

    public const EXTENSION_NAME = 'klarna_onsite_messaging_data';

    /** @var string */
    protected $klarnaOnsiteMessagingSnippet;

    /** @var string */
    protected $klarnaOnsiteMessagingScript;

    /**
     * @param null|array<string,mixed> $data
     */
    public function __construct(?array $data)
    {
        if (empty($data)) {
            return;
        }

        $this->assign($data);
    }

    public function getKlarnaOnsiteMessagingSnippet(): string
    {
        return $this->klarnaOnsiteMessagingSnippet;
    }

    public function getKlarnaOnsiteMessagingScript(): string
    {
        return $this->klarnaOnsiteMessagingScript;
    }
}
