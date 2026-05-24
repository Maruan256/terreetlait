<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\PaymentHandler;

use KlarnaPayment\Components\Client\Response\GenericResponse;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use LogicException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractKlarnaPaymentHandler extends AbstractPaymentHandler
{
    public const CART_HASH_DEFAULT_VERSION = 1;
    public const CART_HASH_CURRENT_VERSION = 2;

    public const FRAUD_STATUS_REJECTED = 'REJECTED';
    public const FRAUD_STATUS_ACCEPTED = 'ACCEPTED';
    public const FRAUD_STATUS_STOPPED = 'STOPPED';

    public const ORDER_STATUS_AUTHORIZED = 'AUTHORIZED';
    public const ORDER_STATUS_CANCELLED = 'CANCELLED';
    public const ORDER_STATUS_CLOSED = 'CLOSED';
    public const ORDER_STATUS_PART_CAPTURED = 'PART_CAPTURED';
    public const ORDER_STATUS_CAPTURED = 'CAPTURED';
    public const ORDER_STATUS_EXPIRED = 'EXPIRED';

    protected EntityRepository $transactionRepository;

    protected RequestStack $requestStack;

    protected function saveTransactionData(OrderTransactionEntity $transaction, GenericResponse $response, Context $context, string $authorizationToken): void
    {
        $customFields = $transaction->getCustomFields() ?? [];

        $customFields = array_merge($customFields, [
            'klarna_order_id' => $response->getResponse()['order_id'],
            CustomFieldInstaller::FIELD_KLARNA_FRAUD_STATUS => $response->getResponse()['fraud_status'],
            'klarna_authorization_token' => $authorizationToken,
        ]);

        $update = [
            'id' => $transaction->getId(),
            'customFields' => $customFields,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($update): void {
            $this->transactionRepository->update([$update], $context);
        });
    }

    protected function fetchRequestData(): RequestDataBag
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new LogicException('missing current request');
        }

        return new RequestDataBag($request->request->all());
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    protected function getOrderTransactionById(string $transactionId, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation("order");

        return $this->transactionRepository->search($criteria, $context)->first();
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    protected function getOrderFromTransaction(string $transactionId, Context $context): OrderEntity
    {
        return $this->getOrderTransactionById($transactionId, $context)?->getOrder();
    }
}
