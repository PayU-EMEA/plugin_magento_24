<?php

namespace PayU\PaymentGateway\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;


interface ReviewOrderPaymentInterface
{
    /**
     * Review order, provide action for accept and deny payment
     * @throws LocalizedException
     */
    public function execute(OrderInterface $order, string $action): void;
}
