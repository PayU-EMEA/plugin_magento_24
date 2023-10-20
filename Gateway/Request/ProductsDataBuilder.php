<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Item;
use PayU\PaymentGateway\Gateway\Helper\Requests;

class ProductsDataBuilder implements BuilderInterface
{
    private Requests $payuRequests;

    public function __construct(
        Requests      $payuRequests
    )
    {
        $this->payuRequests = $payuRequests;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();
        $fullOrder = $payment->getOrder();

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

        if ($fullOrder->getShippingMethod() !== null) {
            $products[] = [
                'quantity' => 1,
                'name' => mb_substr('Shipment [' . $fullOrder->getShippingDescription() . ']', 0, 255),
                'unitPrice' => $this->payuRequests->formatAmount($fullOrder->getShippingAmount())
            ];
        }

        if ($fullOrder->getDiscountAmount() !== null && $fullOrder->getDiscountAmount() != 0) {
            $products[] = [
                'quantity' => 1,
                'name' => 'Discount',
                'unitPrice' => $this->payuRequests->formatAmount($fullOrder->getDiscountAmount())
            ];
        }

        return [
            'body' => [
                'products' => $products
            ]
        ];
    }
}
