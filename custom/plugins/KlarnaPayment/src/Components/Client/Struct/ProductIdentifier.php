<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Struct;

use Shopware\Core\Framework\Struct\Struct;

class ProductIdentifier extends Struct
{
    /** @var null|string */
    protected $brand;

    /** @var null|string */
    protected $categoryPath;

    /** @var null|string */
    protected $globalTradeItemNumber;

    /** @var null|string */
    protected $manufacturerPartNumber;

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getCategoryPath(): ?string
    {
        return $this->categoryPath;
    }

    public function getGlobalTradeItemNumber(): ?string
    {
        return $this->globalTradeItemNumber;
    }

    public function getManufacturerPartNumber(): ?string
    {
        return $this->manufacturerPartNumber;
    }

    public function jsonSerialize(): array
    {
        return [
            'brand'                    => $this->getBrand(),
            // prevent empty strings
            'category_path'            => !empty($this->getCategoryPath()) ? $this->getCategoryPath(): null,
            'global_trade_item_number' => $this->getGlobalTradeItemNumber(),
            'manufacturer_part_number' => $this->getManufacturerPartNumber(),
        ];
    }
}
