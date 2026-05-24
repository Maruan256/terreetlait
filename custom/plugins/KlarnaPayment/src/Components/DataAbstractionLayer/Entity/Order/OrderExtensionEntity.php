<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\DataAbstractionLayer\Entity\Order;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class OrderExtensionEntity extends Entity
{
    use EntityIdTrait;

    /** @var null|string */
    protected $orderId = null;

    /** @var null|string */
    protected $orderVersionId = null;

    /** @var null|OrderEntity */
    protected $order = null;

    /** @var null|string */
    protected $orderAddressHash = null;

    /** @var null|string */
    protected $orderCartHash = null;

    /** @var null|int */
    protected $orderCartHashVersion = null;

    /** @var null|string */
    protected $authorizationToken = null;

    public function getAssign(): array
    {
        return [
            'id'                   => $this->id,
            'versionId'            => $this->versionId,
            'orderId'              => $this->orderId,
            'orderVersionId'       => $this->orderVersionId,
            'orderAddressHash'     => $this->orderAddressHash,
            'orderCartHash'        => $this->orderCartHash,
            'orderCartHashVersion' => $this->orderCartHashVersion,
            'authorizationToken'   => $this->authorizationToken,
        ];
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderVersionId(): ?string
    {
        return $this->orderVersionId;
    }

    public function setOrderVersionId(?string $orderVersionId): void
    {
        $this->orderVersionId = $orderVersionId;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }

    public function getOrderAddressHash(): ?string
    {
        return $this->orderAddressHash;
    }

    public function setOrderAddressHash(?string $orderAddressHash): void
    {
        $this->orderAddressHash = $orderAddressHash;
    }

    public function getOrderCartHash(): ?string
    {
        return $this->orderCartHash;
    }

    public function setOrderCartHash(?string $orderCartHash): void
    {
        $this->orderCartHash = $orderCartHash;
    }

    public function getOrderCartHashVersion(): ?int
    {
        return $this->orderCartHashVersion;
    }

    public function setOrderCartHashVersion(?int $orderCartHashVersion): void
    {
        $this->orderCartHashVersion = $orderCartHashVersion;
    }

    public function getAuthorizationToken(): ?string
    {
        return $this->authorizationToken;
    }

    public function setAuthorizationToken(?string $authorizationToken): void
    {
        $this->authorizationToken = $authorizationToken;
    }
}
