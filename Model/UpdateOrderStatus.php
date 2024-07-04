<?php

namespace PayU\PaymentGateway\Model;

use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayUUpdateOrderStatusInterface;

class UpdateOrderStatus implements PayUUpdateOrderStatusInterface
{
    private PayUConfigInterface $payUConfig;

    public function __construct(
        PayUConfigInterface $payUConfig
    ) {
        $this->payUConfig = $payUConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $type, int $storeId, string $orderId, string $status): \OpenPayU_Result
    {
        $this->payUConfig->setDefaultConfig($type, $storeId);
        $orderStatusUpdate = ['orderId' => $orderId, 'orderStatus' => $status];

        return \OpenPayU_Order::statusUpdate($orderStatusUpdate);
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(string $type, int $storeId, string $orderId)
    {
        $this->payUConfig->setDefaultConfig($type, $storeId);

        return \OpenPayU_Order::cancel($orderId);
    }
}
