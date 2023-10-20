<?php

namespace PayU\PaymentGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\Logger\Logger;

abstract class PayUAbstractClient implements ClientInterface
{
    public const CLIENT_CONFIG_TYPE = 'type';
    public const CLIENT_CONFIG_STORE_ID = 'storeId';
    public const ORDER_ID = 'orderId';
    public const DESCRIPTION = 'description';
    public const AMOUNT = 'amount';

    private Logger $logger;
    private PayUConfigInterface $payUConfig;

    public function __construct(
        PayUConfigInterface $payUConfig,
        Logger $logger
    )
    {
        $this->payUConfig = $payUConfig;
        $this->logger = $logger;
    }

    public function placeRequest(TransferInterface $transferObject): array
    {
        $clientConfig = $transferObject->getClientConfig();

        $this->payUConfig->setDefaultConfig($clientConfig[self::CLIENT_CONFIG_TYPE], $clientConfig[self::CLIENT_CONFIG_STORE_ID]);
        $body = $transferObject->getBody();

        try {
            return $this->payuApi($body);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), ['order' => $body]);
            throw new ClientException(__($e->getMessage()));
        }
    }

    abstract protected function payuApi(array $data): array;
}
