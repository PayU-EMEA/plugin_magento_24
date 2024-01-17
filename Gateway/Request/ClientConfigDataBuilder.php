<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Gateway\Http\Client\PayUAbstractClient;

class ClientConfigDataBuilder implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $paymentDataObject->getOrder();

        return [
            'clientConfig' => [
                PayUAbstractClient::CLIENT_CONFIG_TYPE => $payment->getMethodInstance()->getCode(),
                PayUAbstractClient::CLIENT_CONFIG_STORE_ID => $order->getStoreId()
            ]
        ];
    }
}
