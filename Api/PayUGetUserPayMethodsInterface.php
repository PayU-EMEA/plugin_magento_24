<?php

namespace PayU\PaymentGateway\Api;

interface PayUGetUserPayMethodsInterface
{
    /**
     * Card Tokens code
     */
    public const CARD_TOKENS = 'cardTokens';

    /**
     * Get user payment methods from PayU REST API
     */
    public function execute(?string $email = null, ?int $customerId = null): array;

    /**
     * Convert execute method to Json
     */
    public function toJson(): string;
}
