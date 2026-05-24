<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\CartHasher;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartHasher implements CartHasherInterface
{
    /** @var string */
    private $appSecret;

    public function __construct(string $appSecret)
    {
        $this->appSecret = $appSecret;
    }

    public function generate(Cart $cart, SalesChannelContext $context): string
    {
        $hashData = $this->getHashData($cart, $context);

        return $this->generateHash($hashData);
    }

    public function validate(Cart $cart, string $cartHash, SalesChannelContext $context): bool
    {
        $hashData = $this->getHashData($cart, $context);
        $expected = $this->generateHash($hashData);

        return hash_equals($expected, $cartHash);
    }

    /**
     * @return array<int|string,mixed>
     */
    protected function getHashData(Cart $cart, SalesChannelContext $context): array
    {
        $hashData = [];

        foreach ($cart->getLineItems() as $item) {
            $detail = [
                'id'       => $item->getReferencedId(),
                'type'     => $item->getType(),
                'quantity' => $item->getQuantity(),
            ];

            if ($item->getPrice() !== null) {
                $detail['price'] = $item->getPrice()->getTotalPrice();
            }

            $hashData[] = $detail;
        }

        $hashData['currency']       = $context->getCurrency()->getId();
        $hashData['paymentMethod']  = $context->getPaymentMethod()->getId();
        $hashData['shippingMethod'] = $context->getShippingMethod()->getId();

        if ($context->getCustomer() === null) {
            return $hashData;
        }

        if ($context->getCustomer()->getActiveBillingAddress() !== null) {
            $hashData['billingAddress'] = $this->hydrateAddress($context->getCustomer()->getActiveBillingAddress());
        }

        if ($context->getCustomer()->getActiveShippingAddress() !== null) {
            $hashData['shippingAddress'] = $this->hydrateAddress($context->getCustomer()->getActiveShippingAddress());
        }

        $hashData['customer'] = [
            'language' => $context->getCustomer()->getLanguageId(),
            'email'    => $context->getCustomer()->getEmail(),
        ];

        if ($context->getCustomer()->getBirthday() !== null) {
            $hashData['birthday'] = $context->getCustomer()->getBirthday()->format(DATE_W3C);
        }

        return $hashData;
    }

    /**
     * @param array<int|string,mixed> $hashData
     */
    private function generateHash(array $hashData): string
    {
        $json = json_encode($hashData, JSON_PRESERVE_ZERO_FRACTION);

        if (empty($json)) {
            throw new \LogicException('could not generate hash');
        }

        if (empty($this->appSecret)) {
            throw new \LogicException('empty app secret');
        }

        return hash_hmac('sha256', $json, $this->appSecret);
    }

    /**
     * @return array<string,null|string>
     */
    private function hydrateAddress(CustomerAddressEntity $address): array
    {
        return [
            'title'           => $address->getTitle(),
            'firstname'       => $address->getFirstName(),
            'lastname'        => $address->getLastName(),
            'street'          => $address->getStreet(),
            'addressaddition' => $address->getAdditionalAddressLine1(),
            'zip'             => $address->getZipcode(),
            'city'            => $address->getCity(),
            'country'         => $address->getCountryId(),
            'region'          => $address->getCountryStateId(),
        ];
    }
}
