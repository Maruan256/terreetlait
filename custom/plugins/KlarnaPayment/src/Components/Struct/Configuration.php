<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Struct;

use Shopware\Core\Framework\Struct\Struct;

class Configuration extends Struct
{
    /** @var array<string,mixed> */
    protected $configuration = [];

    /**
     * @param array<string,mixed> $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param mixed|string $default
     *
     * @return mixed|string
     */
    public function get(string $key, $default = '')
    {
        if (!array_key_exists($key, $this->configuration)) {
            return $default;
        }

        return $this->configuration[$key];
    }
}
