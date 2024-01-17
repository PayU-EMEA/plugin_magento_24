<?php

namespace PayU\PaymentGateway\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface RepayOrderResolverInterface
{
    public function execute(OrderInterface $order, string $method, string $payUMethod, string $payUMethodType, array $payuBrowser): array;
}
