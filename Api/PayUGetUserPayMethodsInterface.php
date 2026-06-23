<?php

namespace PayU\PaymentGateway\Api;

/**
 * Interface PayUGetUserPayMethodsInterface
 * @package PayU\PaymentGateway\Api
 */
interface PayUGetUserPayMethodsInterface
{
    /**
     * Card Tokens code
     */
    const CARD_TOKENS = 'cardTokens';

    /**
     * Get user payment methods from PayU REST API
     *
     * @param string|null $email
     * @param int|null $customerId
     *
     * @return array
     */
    public function execute($email = null, $customerId = null);

    /**
     * Convert execute method to Json
     *
     * @return string
     */
    public function toJson();
}
