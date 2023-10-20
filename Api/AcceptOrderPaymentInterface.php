<?php

namespace PayU\PaymentGateway\Api;

use Magento\Payment\Gateway\Command\CommandException;

interface AcceptOrderPaymentInterface
{
    /**
     * Accept order payment by capture payment, generate invoice and send email invoice email to customer
     *
     * @throws CommandException
     */
    public function execute(string $txnId, float $amount, string $paymentId = null): void;
}
