<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\EndpointHelper;

use Doctrine\DBAL\Connection;
use KlarnaPayment\Components\Client\Request\RequestInterface;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DbalConnectionHelper;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class EndpointHelper implements EndpointHelperInterface
{
    public const SESSION_KEY_KLARNA = 'klarna_payment_iso_mapper_klarna_id';
    public const SESSION_KEY_SW     = 'klarna_payment_iso_mapper_sw_id';

    /** @var Connection $connection */
    private $connection;
    /** @var null|SessionInterface */
    private $session;
    /** @var null|RequestStack */
    private $requestStack;

    public function __construct(
        Connection $connection,
        ?SessionInterface $session = null,
        ?RequestStack $requestStack = null
    ) {
        $this->connection   = $connection;
        $this->session      = $session;
        $this->requestStack = $requestStack;
    }

    public function resolveEndpointRegion(RequestInterface $request): string
    {
        if (method_exists($request, 'getPurchaseCountry') && !empty($request->getPurchaseCountry())) {
            if ($request->getPurchaseCountry() === PaymentMethodInstaller::KLARNA_API_REGION_US) {
                return $request->getPurchaseCountry();
            }

            return PaymentMethodInstaller::KLARNA_API_REGION_EU;
        }

        if (method_exists($request, 'getOrderId') && !empty($request->getOrderId())) {
            $internalIsoMapper = $this->getInternalIsoMapper(self::SESSION_KEY_SW);

            if (array_key_exists($request->getOrderId(), $internalIsoMapper)) {
                return $internalIsoMapper[$request->getOrderId()];
            }

            $billingIsoCountry = $this->getBillingCountryISOCodeByOrderId($request->getOrderId());

            $internalIsoMapper[$request->getOrderId()] = $billingIsoCountry;

            $this->updateIsoMapping(self::SESSION_KEY_SW, $internalIsoMapper);

            return $billingIsoCountry;
        }

        if (method_exists($request, 'getKlarnaOrderId')) {
            $internalIsoMapper = $this->getInternalIsoMapper(self::SESSION_KEY_KLARNA);

            if (array_key_exists($request->getKlarnaOrderId(), $internalIsoMapper)) {
                return $internalIsoMapper[$request->getKlarnaOrderId()];
            }

            $billingIsoCountry = $this->getBillingCountryISOCodeByKlarnaOrderId($request->getKlarnaOrderId());

            $internalIsoMapper[$request->getKlarnaOrderId()] = $billingIsoCountry;

            $this->updateIsoMapping(self::SESSION_KEY_KLARNA, $internalIsoMapper);

            return $billingIsoCountry;
        }

        return PaymentMethodInstaller::KLARNA_API_REGION_EU;
    }

    private function getInternalIsoMapper(string $sessionKey): array
    {
        if (!$this->session instanceof SessionInterface
            && $this->requestStack !== null
            && method_exists($this->requestStack, 'getSession')) {
            try {
                $this->session = $this->requestStack->getSession();
            } catch (\Throwable $t) {
                // silentfail
            }
        }

        if ($this->session instanceof SessionInterface) {
            return $this->session->get($sessionKey, []);
        }

        return [];
    }

    private function updateIsoMapping(string $sessionKey, array $isoMapping): void
    {
        if (!$this->session instanceof SessionInterface
            && $this->requestStack !== null
            && method_exists($this->requestStack, 'getSession')) {
            try {
                $this->session = $this->requestStack->getSession();
            } catch (\Throwable $t) {
                // silentfail
            }
        }

        if ($this->session instanceof SessionInterface) {
            $this->session->set($sessionKey, $isoMapping);
        }
    }

    private function getBillingCountryISOCodeByOrderId(string $orderId): string
    {
        $isoCode = DbalConnectionHelper::fetchColumn($this->connection,
            'SELECT c.iso
            FROM `order` o
            LEFT JOIN order_address oa ON o.billing_address_id = oa.id
            LEFT JOIN country c ON oa.country_id = c.id
            WHERE o.id = UNHEX(:orderId)
            LIMIT 1',
            ['orderId' => $orderId]
        );

        if ($isoCode === PaymentMethodInstaller::KLARNA_API_REGION_US) {
            return $isoCode;
        }

        return PaymentMethodInstaller::KLARNA_API_REGION_EU;
    }

    private function getBillingCountryISOCodeByKlarnaOrderId(string $klarnaOrderId): string
    {
        $isoCode = DbalConnectionHelper::fetchColumn($this->connection,
            'SELECT c.iso
            FROM `order` o
            LEFT JOIN order_transaction ot ON o.id = ot.order_id
            LEFT JOIN order_address oa ON o.billing_address_id = oa.id
            LEFT JOIN country c ON oa.country_id = c.id
            WHERE ot.custom_fields LIKE :orderId',
            ['orderId' => '%' . $klarnaOrderId . '%']
        );

        if ($isoCode === PaymentMethodInstaller::KLARNA_API_REGION_US) {
            return $isoCode;
        }

        return PaymentMethodInstaller::KLARNA_API_REGION_EU;
    }
}
