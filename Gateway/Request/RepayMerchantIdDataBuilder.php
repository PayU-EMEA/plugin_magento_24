<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Gateway\Helper\RepaySubjectReader;

class RepayMerchantIdDataBuilder implements BuilderInterface
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
        $order = RepaySubjectReader::readOrder($buildSubject);
        $method = RepaySubjectReader::readMethod($buildSubject);

        $this->gatewayConfig->setMethodCode($method);
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
