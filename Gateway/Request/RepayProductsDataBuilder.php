<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Item;
use PayU\PaymentGateway\Gateway\Helper\RepaySubjectReader;
use PayU\PaymentGateway\Gateway\Helper\Requests;

class RepayProductsDataBuilder implements BuilderInterface
{
    private Requests $payuRequests;

    public function __construct(
        Requests $payuRequests
    )
    {
        $this->payuRequests = $payuRequests;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $order = RepaySubjectReader::readOrder($buildSubject);

        $products = [];
        $i = 0;

        /** @var Item $product */
        foreach ($order->getItems() as $product) {
            if (!$product->isDeleted() && !$product->getParentItemId()) {
                if ($product->getParentItem() === null) {
                    $quantity = $product->getQtyOrdered();
                    $name = $product->getName();

                    if (fmod($quantity, 1) !== 0.0) {
                        $quantity = ceil($quantity);
                        $name = '[' . $product->getQtyOrdered() . '] ' . $name;
                    } else {
                        $quantity = floor($quantity);
                    }

                    if ($quantity === 0) {
                        $quantity = 1;
                    }


                    $products[$i] = [
                        'quantity' => $quantity,
                        'name' => mb_substr($name, 0, 255),
                        'unitPrice' => $this->payuRequests->formatAmount($product->getPriceInclTax())
                    ];

                    if ($product->getIsVirtual()) {
                        $products[$i]['virtual'] = true;
                    }
                }
                $i++;
            }
        }

        if ($order->getShippingMethod() !== null) {
            $products[] = [
                'quantity' => 1,
                'name' => mb_substr('Shipment [' . $order->getShippingDescription() . ']', 0, 255),
                'unitPrice' => $this->payuRequests->formatAmount($order->getShippingAmount())
            ];
        }

        if ($order->getDiscountAmount() !== null && $order->getDiscountAmount() != 0) {
            $products[] = [
                'quantity' => 1,
                'name' => 'Discount',
                'unitPrice' => $this->payuRequests->formatAmount($order->getDiscountAmount())
            ];
        }

        return [
            'body' => [
                'products' => $products
            ]
        ];
    }
}
