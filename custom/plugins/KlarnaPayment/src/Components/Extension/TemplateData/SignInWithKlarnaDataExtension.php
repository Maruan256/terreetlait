<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Extension\TemplateData;

use Shopware\Core\Framework\Struct\Struct;

class SignInWithKlarnaDataExtension extends Struct
{
    public const EXTENSION_NAME = 'signInWithKlarna';

    /** @var string */
    protected $clientKey;

    /** @var string */
    protected $theme;

    /** @var string */
    protected $shape;

    /** @var string */
    protected $dataKeys;

    public function __construct(
        string $clientKey,
        string $theme,
        string $shape,
        string $dataKeys
    ) {
        $this->clientKey = $clientKey;
        $this->theme = $theme;
        $this->shape = $shape;
        $this->dataKeys = $dataKeys;
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

    public function getDataKeys(): string
    {
        return $this->dataKeys;
    }
}
