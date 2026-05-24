<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\ConfigReader\ConfigReader;
use KlarnaPayment\Components\Exception\GetKlarnaOrderException;
use KlarnaPayment\Components\Exception\KlarnaOrderIdNotFoundException;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Components\Helper\OrderValidator\OrderValidatorInterface;
use KlarnaPayment\Core\Framework\ContextScope;
use Monolog\Logger;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PostWriteValidationEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderChangeEventListener implements EventSubscriberInterface
{
    /** @var OrderFetcherInterface */
    private $orderFetcher;

    /** @var TranslatorInterface */
    private $translator;

    /** @var OrderValidatorInterface */
    private $orderValidator;

    /** @var string */
    private $currentLocale;

    /** @var RequestStack */
    private $requestStack;

    private $previousOrder = null;

    /** @var ConfigReader */
    private $configReader;

    /** @var Logger */
    private $logger;

    public function __construct(
        OrderFetcherInterface $orderFetcher,
        TranslatorInterface $translator,
        OrderValidatorInterface $orderValidator,
        RequestStack $requestStack,
        ConfigReader $configReader,
        Logger $logger
    ) {
        $this->orderFetcher = $orderFetcher;
        $this->translator = $translator;
        $this->orderValidator = $orderValidator;
        $this->requestStack = $requestStack;
        $this->configReader = $configReader;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostWriteValidationEvent::class => 'validateKlarnaOrder',
            PreWriteValidationEvent::class => 'savePreviousOrderData'
        ];
    }

    public function savePreviousOrderData(PreWriteValidationEvent $event): void
    {
        $previousOrder = $this->getOrderFromWriteCommands($event);

        if ($previousOrder === null || !$this->orderValidator->isKlarnaOrder($previousOrder)) {
            return;
        }

        $this->previousOrder = $previousOrder;
    }

    /**
     * @see \KlarnaPayment\Components\Controller\Administration\OrderUpdateController::update Change accordingly to keep functionality synchronized
     */
    public function validateKlarnaOrder(PostWriteValidationEvent $event): void
    {
        if ($event->getContext()->getScope() === ContextScope::INTERNAL_SCOPE) {
            // only check user generated changes
            return;
        }

        if ($event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            // No live data change, just draft versions
            return;
        }

        $order = $this->getOrderFromWriteCommands($event);

        if ($order === null || !$this->orderValidator->isKlarnaOrder($order)) {
            return;
        }

        $this->setCurrentLocale($event);

        try {
            $this->validateOrderAddress($order, $event);
            $this->validateLineItems($order, $event);
        } catch (GetKlarnaOrderException $exception) {
            $this->logger->error("Validate lineItemHash", ["code" => $exception->getErrorCode(), "response" => $exception->getResponse()]);
        } catch (KlarnaOrderIdNotFoundException $exception) {
            $this->logger->error("Validate orderAddressHash", ["message" => $exception->getMessage()]);
        }
    }

    private function setCurrentLocale(PostWriteValidationEvent $event): void
    {
        $languages = $event->getWriteContext()->getLanguages();
        $this->currentLocale = $languages[$event->getContext()->getLanguageId()]['code'] ?? 'en-GB';
    }

    /**
     * @throws GetKlarnaOrderException
     */
    private function validateLineItems(OrderEntity $orderEntity, PostWriteValidationEvent $event): void
    {
        if ($this->orderValidator->validateAndInitLineItemsHash($orderEntity, $event->getContext())) {
            return;
        }

        $configuration = $this->configReader->read($orderEntity->getSalesChannelId());

        // Dont throw a ConstraintViolation
        if ($configuration->get('klarnaDisableLineItemValidation', false)) {
            $this->logger->info('LineItem validation disabled: ', [
                "shopware_line_items" => $orderEntity->getLineItems()?->jsonSerialize(),
                "klarnaDisableLineItemValidation" => $configuration->get('klarnaDisableLineItemValidation', false)
            ]);

            return;
        }

        $violation = new ConstraintViolation(
            $this->translator->trans('KlarnaPayment.errorMessages.lineItemChangeDeclined', [], null, $this->currentLocale),
            '',
            [],
            '',
            '',
            ''
        );

        $violations = new ConstraintViolationList([$violation]);

        $event->getExceptions()->add(new WriteConstraintViolationException($violations));
    }

    /**
     * @throws GetKlarnaOrderException
     */
    private function validateOrderAddress(OrderEntity $orderEntity, PostWriteValidationEvent $event): void
    {
        $validationErrors = [];

        if ($this->orderValidator->validateAndInitOrderAddressHash($orderEntity, $this->previousOrder, $event->getContext(), $validationErrors)) {
            $this->previousOrder = null;

            return;
        }

        $this->previousOrder = null;

        $message = '';
        $params = [];
        if ($validationErrors !== []) {
            $message = json_encode($validationErrors['message']);
            $params = $validationErrors['params'];
        }

        $violation = new ConstraintViolation(
            $this->translator->trans('KlarnaPayment.errorMessages.addressChangeDeclined', [], null, $this->currentLocale),
            $message,
            $params,
            '',
            '',
            ''
        );

        $violations = new ConstraintViolationList([$violation]);

        $event->getExceptions()->add(new WriteConstraintViolationException($violations));
    }

    private function getOrderFromWriteCommands(PostWriteValidationEvent|PreWriteValidationEvent $event): ?OrderEntity
    {
        foreach ($event->getCommands() as $command) {
            if (!($command instanceof UpdateCommand)) {
                continue;
            }

            $primaryKeys = $command->getPrimaryKey();

            if (!array_key_exists('id', $primaryKeys) || empty($primaryKeys['id'])) {
                continue;
            }

            if ($command->getEntityName() === OrderAddressDefinition::ENTITY_NAME) {
                return $this->orderFetcher->getOrderFromOrderAddress($primaryKeys['id'], $event->getContext());
            }

            if ($command->getEntityName() === OrderLineItemDefinition::ENTITY_NAME) {
                return $this->orderFetcher->getOrderFromOrderLineItem($primaryKeys['id'], $event->getContext());
            }

            if ($command->getEntityName() === OrderDefinition::ENTITY_NAME) {
                return $this->orderFetcher->getOrderFromOrder($primaryKeys['id'], $event->getContext());
            }
        }

        return null;
    }
}
