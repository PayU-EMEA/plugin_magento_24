<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class MerchantIdDataBuilder implements BuilderInterface
{
    private GatewayConfig $gatewayConfig;


    public function __construct(GatewayConfig $gatewayConfig)
    {
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();

        $this->gatewayConfig->setMethodCode($payment->getMethodInstance()->getCode());
        $envPrefix = (bool)$this->gatewayConfig->getValue('environment', $order->getStoreId()) ? 'sandbox_' : '';

        $this->gatewayConfig->setMethodCode('payu');
        $merchantPosId = $this->gatewayConfig->getValue($envPrefix . 'pos_id', $order->getStoreId());

        return [
            'body' => [
                'merchantPosId' => $merchantPosId
            ]
        ];
    }
}
