<?php

namespace PayU\PaymentGateway\Api;

interface GetAvailableLocaleInterface
{
    /**
     * Get current language Provide option to get only language from array parametes
     */
    public function execute(array $availableLanguages = []): string;
}
