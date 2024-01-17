<?php

namespace PayU\PaymentGateway\Api;

interface CancelOrderPaymentInterface
{
    public function execute(string $txnId, float $amount): void;
}
