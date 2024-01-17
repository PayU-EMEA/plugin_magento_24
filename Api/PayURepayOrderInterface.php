<?php

namespace PayU\PaymentGateway\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface PayURepayOrderInterface
{
    public function execute(OrderInterface $order, string $method, string $payUMethodType, string $payUMethod, array $payuBrowser, string $transactionId): void;
}
