<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Gateway\Helper\RepaySubjectReader;
use PayU\PaymentGateway\Gateway\Helper\Requests;

class RepayAmountCurrencyDataBuilder implements BuilderInterface
{
    private GatewayConfig $gatewayConfig;
    private Requests $payuRequests;

    public function __construct(
        GatewayConfig $gatewayConfig,
        Requests      $payuRequests
    )
    {
        $this->gatewayConfig = $gatewayConfig;
        $this->payuRequests = $payuRequests;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $order = RepaySubjectReader::readOrder($buildSubject);

        $totalAmount = $order->getGrandTotal();
        $currencyCode = $order->getOrderCurrencyCode();

        return [
            'body' => [
                'totalAmount' => $this->payuRequests->formatAmount($totalAmount),
                'currencyCode' => $currencyCode,
            ]
        ];
    }
}
