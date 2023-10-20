<?php

namespace PayU\PaymentGateway\Api;

/**
 * Interface PayUUpdateOrderStatusrInterface
 * @package PayU\PaymentGateway\Api
 */
interface PayUUpdateOrderStatusInterface
{
    /**
     * Update status of order
     * @throws \OpenPayU_Exception
     */
    public function update(string $type, int $storeId, string $orderId, string $status): \OpenPayU_Result;

    /**
     * Cancel order action
     * @throws \OpenPayU_Exception
     */
    public function cancel(string $type, int $storeId, string $orderId): \OpenPayU_Result|null;
}
