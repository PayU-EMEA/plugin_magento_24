<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Gateway\Helper\Requests;

class AmountCurrencyDataBuilder implements BuilderInterface
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
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();

        $fullOrder = $payment->getOrder();
        $totalAmount = $fullOrder->getGrandTotal();
        $currencyCode = $fullOrder->getOrderCurrencyCode();

        return [
            'body' => [
                'totalAmount' => $this->payuRequests->formatAmount($totalAmount),
                'currencyCode' => $currencyCode,
            ]
        ];
    }
}
