<?php

namespace PayU\PaymentGateway\Api;

interface RepaymentResolverInterface
{
    /**
     * Is order can be repay
     */
    public function isRepayment(int $orderId): bool;

    /**
     * Is repayment active for any method
     */
    public function isAnyRepaymentEnabled(): bool;
}
