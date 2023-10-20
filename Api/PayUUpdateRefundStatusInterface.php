<?php

namespace PayU\PaymentGateway\Api;

interface PayUUpdateRefundStatusInterface
{
    /**
     * Refund status finalized
     */
    const STATUS_FINALIZED = 'FINALIZED';

    /**
     * Refund status canceled
     */
    const STATUS_CANCELED = 'CANCELED';

    /**
     * Cancel refund
     */
    public function cancel(string $extOrderId): void;

    /**
     * Update refund status of order
     */
    public function addSuccessMessage(string $extOrderId): void;
}
