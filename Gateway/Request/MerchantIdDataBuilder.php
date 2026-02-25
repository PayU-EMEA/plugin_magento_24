<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Model\Config;

class MerchantIdDataBuilder implements BuilderInterface
{
    private GatewayConfig $gatewayConfig;
    private Config $config;

    public function __construct(
        GatewayConfig $gatewayConfig,
        Config $config
    )
    {
        $this->gatewayConfig = $gatewayConfig;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $storeId = $order->getStoreId();

        $isSandboxEnvironment = $this->config->isSandboxEnv($storeId);
        $envPrefix = $isSandboxEnvironment ? 'sandbox_' : '';

        $this->gatewayConfig->setMethodCode('payu');
        $merchantPosId = $this->gatewayConfig->getValue($envPrefix . 'pos_id', $storeId);

        return [
            'body' => [
                'merchantPosId' => $merchantPosId
            ]
        ];
    }
}
