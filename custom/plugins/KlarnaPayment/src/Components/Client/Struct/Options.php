<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Struct;

use Shopware\Core\Framework\Struct\Struct;

class Options extends Struct
{
    /** @var array<int|string,mixed> */
    protected $options = [];

    /**
     * @param array<int|string,mixed> $options
     */
    public function assign(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * @return array<int|string,mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function jsonSerialize(): array
    {
        return $this->getOptions();
    }
}
