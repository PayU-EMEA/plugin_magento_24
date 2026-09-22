<?php

namespace PayU\PaymentGateway\Api;

use Magento\Payment\Gateway\Command\CommandException;

/**
 * Interface WaitingOrderPaymentInterface
 */
interface WaitingOrderPaymentInterface
{

    /**
     * Set order status by status from PayU REST API
     * @throws CommandException
     */
    public function execute(string $txnId): void;
}
