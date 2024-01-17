<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Gateway\Helper\RepaySubjectReader;
use PayU\PaymentGateway\Gateway\Http\Client\PayUAbstractClient;

class RepayClientConfigDataBuilder implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $order = RepaySubjectReader::readOrder($buildSubject);
        $method = RepaySubjectReader::readMethod($buildSubject);

        return [
            'clientConfig' => [
                PayUAbstractClient::CLIENT_CONFIG_TYPE => $method,
                PayUAbstractClient::CLIENT_CONFIG_STORE_ID => $order->getStoreId()
            ]
        ];
    }
}
