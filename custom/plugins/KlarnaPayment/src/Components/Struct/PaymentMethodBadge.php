<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Struct;

use Shopware\Core\Framework\Struct\Struct;

class PaymentMethodBadge extends Struct
{
    public const EXTENSION_NAME = 'KLARNA_BADGE';

    public const TYPE_CHECKOUT = 'checkout';
    public const TYPE_PAYMENTS = 'payments';

    /** @var string */
    protected $paymentType = self::TYPE_CHECKOUT;

    /** @var string */
    protected $countryCode = 'xx_XX';

    /** @var string */
    protected $style = 'long-blue';

    /** @var int */
    protected $width = 400;

    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function getWidth(): int
    {
        return $this->width;
    }
}
