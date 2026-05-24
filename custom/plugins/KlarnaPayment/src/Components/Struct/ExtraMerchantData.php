<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Struct;

use Shopware\Core\Framework\Struct\Struct;

class ExtraMerchantData extends Struct
{
    /** @var null|string */
    protected $merchantData;

    /** @var null|array<string,mixed> */
    protected $attachment;

    public function getMerchantData(): ?string
    {
        if (empty($this->merchantData)) {
            return null;
        }

        return $this->merchantData;
    }

    /**
     * @return null|array<string,mixed>
     */
    public function getAttachment(): ?array
    {
        if ($this->attachment === null || empty($this->attachment)) {
            return null;
        }

        return $this->attachment;
    }
}
