<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Response\GetOrder;

use KlarnaPayment\Components\Client\Hydrator\Struct\Address\AddressStructHydratorInterface;
use KlarnaPayment\Components\Client\Response\GenericResponse;
use KlarnaPayment\Components\Client\Response\GetOrderResponse;
use KlarnaPayment\Components\Client\Struct\LineItem;
use KlarnaPayment\Components\Client\Struct\ProductIdentifier;
use Shopware\Core\Framework\Context;

class GetOrderResponseHydrator implements GetOrderResponseHydratorInterface
{
    /** @var AddressStructHydratorInterface */
    private $addressStructHydrator;

    public function __construct(
        AddressStructHydratorInterface $addressStructHydrator
    ) {
        $this->addressStructHydrator = $addressStructHydrator;
    }

    public function hydrate(GenericResponse $genericResponse, Context $context): GetOrderResponse
    {
        $order = $genericResponse->getResponse();

        $expiryDate = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $order['expires_at']);

        $response = new GetOrderResponse();
        $response->assign([
            'orderId'              => $order['order_id'],
            'orderNumber'          => $order['merchant_reference1'],
            'fraudStatus'          => $order['fraud_status'],
            'orderStatus'          => $order['status'],
            'currency'             => $order['purchase_currency'],
            'orderAmount'          => $order['order_amount'] / 100,
            'expiryDate'           => $expiryDate,
            'reference'            => $order['klarna_reference'],
            'capturedAmount'       => $order['captured_amount'] / 100,
            'remainingAmount'      => $order['remaining_authorized_amount'] / 100,
            'refundedAmount'       => $order['refunded_amount'] / 100,
            'orderLines'           => $this->hydrateOrderLines($order),
            'lastCaptureId'        => $this->getLastCaptureId($order),
            'initialPaymentMethod' => $order['initial_payment_method']['description'],
            'billingAddress'       => $this->addressStructHydrator->hydrateFromResponse($order['billing_address'], $context),
            'shippingAddress'      => $this->addressStructHydrator->hydrateFromResponse($order['shipping_address'], $context)
        ]);

        return $response;
    }

    /**
     * @param array<string,mixed> $order
     */
    private function getLastCaptureId(array $order): ?string
    {
        if (!array_key_exists('captures', $order) || empty($order['captures'])) {
            return null;
        }

        $lastCapture = end($order['captures']);

        return $lastCapture['capture_id'];
    }

    /**
     * @param array<string,mixed> $order
     *
     * @return LineItem[]
     */
    private function hydrateOrderLines(array $order): array
    {
        $lineItems = [];

        foreach ($order['order_lines'] as $orderLine) {
            $lineItem = new LineItem();

            if (!empty($orderLine['product_identifiers'])) {
                $productIdentifier = new ProductIdentifier();
                $categoryPath = $orderLine['product_identifiers']['category_path'] ?? null;

                $productIdentifier->assign([
                    'brand'                  => $orderLine['product_identifiers']['brand'] ?? null,
                    // prevent empty strings
                    'categoryPath'           => !empty($categoryPath) ? $categoryPath : null,
                    'globalTradeItemNumber'  => $orderLine['product_identifiers']['global_trade_item_number'] ?? null,
                    'manufacturerPartNumber' => $orderLine['product_identifiers']['manufacturer_part_number'] ?? null,
                ]);

                $lineItem->assign([
                    'productIdentifier' => $productIdentifier,
                ]);
            }

            $capturedQuantity = $this->getProcessedQuantity($orderLine, $order['captures']);
            $refundedQuantity = $this->getProcessedQuantity($orderLine, $order['refunds']);

            $lineItem->assign([
                'reference'        => $orderLine['reference'],
                'type'             => $orderLine['type'],
                'quantity'         => $orderLine['quantity'],
                'capturedQuantity' => $capturedQuantity,
                'refundedQuantity' => $refundedQuantity,
                'quantityUnit'     => $orderLine['quantity_unit'] ?? null,
                'name'             => $orderLine['name'],
                'totalAmount'      => $orderLine['total_amount'] / 100,
                'unitPrice'        => $orderLine['unit_price'] / 100,
                'taxRate'          => $orderLine['tax_rate'] / 100,
                'totalTaxAmount'   => $orderLine['total_tax_amount'] / 100,
                'imageUrl'         => $orderLine["image_url"] ?? null,
                'productUrl'       => $orderLine["product_url"] ?? null
            ]);

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    /**
     * @param array<string,mixed> $orderLine
     * @param array<string,mixed> $elements
     */
    private function getProcessedQuantity(array $orderLine, array $elements): int
    {
        $quantity = 0;

        if (empty($elements)) {
            return $quantity;
        }

        $filter = static function (array $capturedOrderLine) use ($orderLine) {
            return $orderLine['reference'] === $capturedOrderLine['reference'];
        };

        foreach ($elements as $capture) {
            /** @var array<string,mixed> $orderLines */
            $orderLines = array_filter($capture['order_lines'], $filter);

            foreach ($orderLines as $capturedOrderLine) {
                $quantity += $capturedOrderLine['quantity'];
            }
        }

        if ($quantity > $orderLine['quantity']) {
            $quantity = $orderLine['quantity'];
        }

        return $quantity;
    }
}
