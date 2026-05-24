<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Extension\TemplateData;

use Shopware\Core\Framework\Struct\Struct;

class ExpressDataExtension extends Struct
{
    public const EXTENSION_NAME = 'klarna_express_data';

    /** @var string */
    protected $clientKey;

    /** @var string */
    protected $theme;

    /** @var string */
    protected $shape;

    public function __construct(
        string $clientKey,
        string $theme,
        string $shape
    ) {
        $this->clientKey = $clientKey;
        $this->theme     = $theme;
        $this->shape     = $shape;
    }

    public function getClientKey(): string
    {
        return $this->clientKey;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getShape(): string
    {
        return $this->shape;
    }
}
