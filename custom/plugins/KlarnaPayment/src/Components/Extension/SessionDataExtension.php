<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Extension;

use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Framework\Struct\Struct;

class SessionDataExtension extends Struct
{
    public const EXTENSION_NAME = 'klarna_session_data';

    /** @var string */
    protected $sessionId = '';

    /** @var string */
    protected $clientToken = '';

    /** @var array<string,mixed> */
    protected $paymentMethodCategories = [];

    /** @var string */
    protected $selectedPaymentMethodCategory;

    /** @var string */
    protected $cartHash = '';

    /** @var string */
    protected $klarnaCartHash = '';

    /** @var string */
    protected $klarnaCartToken = '';

    /** @var array<string,mixed> */
    protected $customerData = [];

    /** @var bool */
    protected $useAuthorizationCallback = false;

    /** @var bool */
    protected $isKlarnaExpress = false;

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getClientToken(): string
    {
        return $this->clientToken;
    }

    /**
     * @return array<string,mixed>
     */
    public function getPaymentMethodCategories(): array
    {
        return $this->paymentMethodCategories;
    }

    public function getSelectedPaymentMethodCategory(): string
    {
        return $this->selectedPaymentMethodCategory;
    }

    public function getCartHash(): string
    {
        return $this->cartHash;
    }

    public function getKlarnaCartHash(): string
    {
        return $this->klarnaCartHash;
    }

    public function getKlarnaCartToken(): string
    {
        return $this->klarnaCartToken;
    }

    /**
     * @return array<string,mixed>
     */
    public function getCustomerData(): array
    {
        return $this->customerData;
    }

    public function getPaymentMethodCategory(string $identifier): ?array
    {
        foreach ($this->paymentMethodCategories as $paymentMethodCategory) {
            if ($paymentMethodCategory['identifier'] === $identifier) {
                return $paymentMethodCategory;
            }
        }

        return null;
    }

    public function isUseAuthorizationCallback(): bool
    {
        return $this->useAuthorizationCallback;
    }

    public function isKlarnaExpress(): bool
    {
        return $this->isKlarnaExpress;
    }
}
