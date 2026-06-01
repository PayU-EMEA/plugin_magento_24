<?php

namespace PayU\PaymentGateway\Api;

/**
 * Interface PayUGetPayMethodsInterface
 * @package PayU\PaymentGateway\Api
 */
interface PayUGetPayMethodsInterface
{
    /**
     * Paymethod status ENABLED
     */
    public const PAYMETHOD_STATUS_ENABLED = 'ENABLED';

    /**
     * Get all pay methods for selected POS from PayU REST API
     */
    public function getAllAvailablePayMethods(?float $totalAmount = null): array;

    /**
     * Get pay methods for PBL
     */
    public function getAllPayMethodsForPbl(bool $filterCreditMethods, ?float $totalAmount = null): array;
}
