<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Validator;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Shopware\Core\System\StateMachine\StateMachineEntity;

class OrderTransitionChangeValidator
{
    public const STATUS_ENTITIES = [
        'orderStatus'    => OrderDefinition::ENTITY_NAME,
        'deliveryStatus' => OrderDeliveryDefinition::ENTITY_NAME,
    ];

    public const CAPTURE_SETTING_KEYS = [
        'orderStatus'    => 'captureOrderStatus',
        'deliveryStatus' => 'captureDeliveryStatus',
    ];

    public const REFUND_SETTING_KEYS = [
        'orderStatus'    => 'refundOrderStatus',
        'deliveryStatus' => 'refundDeliveryStatus',
    ];

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var EntityRepository */
    private $stateMachineStateRepository;

    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $stateMachineStateRepository
    ) {
        $this->configReader                = $configReader;
        $this->stateMachineStateRepository = $stateMachineStateRepository;
    }

    public function isAutomaticCapture(?string $entityName, string $stateTechnicalName, string $salesChannelId, Context $context): bool
    {
        $config = $this->configReader->read($salesChannelId);
        $type   = (string) $config->get('automaticCapture');

        if ($entityName !== null && !$this->hasDefinedType($type, $entityName)) {
            return false;
        }

        if (!isset(self::CAPTURE_SETTING_KEYS[$type])) {
            return false;
        }

        return $this->isCorrectStateTransition(
            (string) $config->get(self::CAPTURE_SETTING_KEYS[$type]),
            $stateTechnicalName,
            $context
        );
    }

    public function isAutomaticRefund(StateMachineTransitionEvent $transitionEvent, string $salesChannelId): bool
    {
        $config = $this->configReader->read($salesChannelId);
        $type   = (string) $config->get('automaticRefund');

        if (!$this->hasDefinedType($type, $transitionEvent->getEntityName())) {
            return false;
        }

        return $this->isCorrectStateTransition(
            (string) $config->get(self::REFUND_SETTING_KEYS[$type]),
            $transitionEvent->getToPlace()->getTechnicalName(),
            $transitionEvent->getContext()
        );
    }

    public function isAutomaticCancel(StateMachineTransitionEvent $transitionEvent): bool
    {
        if ($transitionEvent->getToPlace()->getTechnicalName() === OrderTransactionStates::STATE_CANCELLED) {
            return true;
        }

        if ($transitionEvent->getToPlace()->getTechnicalName() === OrderStates::STATE_CANCELLED) {
            return true;
        }

        return false;
    }

    private function hasDefinedType(string $type, string $entityName): bool
    {
        if (!array_key_exists($type, self::STATUS_ENTITIES)) {
            return false;
        }

        if (self::STATUS_ENTITIES[$type] !== $entityName) {
            return false;
        }

        return true;
    }

    private function isCorrectStateTransition(string $typeUuid, string $technicalName, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $typeUuid));

        $stateMachineSearchResult = $this->stateMachineStateRepository->search($criteria, $context);

        if ($stateMachineSearchResult->count() <= 0) {
            return false;
        }

        $stateMachineSearchResultElement = $stateMachineSearchResult->first();

        if (!$stateMachineSearchResultElement) {
            return false;
        }

        /** @var StateMachineEntity $stateMachineSearchResultElement */
        return $stateMachineSearchResultElement->getTechnicalName() === $technicalName;
    }
}
