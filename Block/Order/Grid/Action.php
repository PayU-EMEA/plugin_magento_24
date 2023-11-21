<?php

namespace PayU\PaymentGateway\Block\Order\Grid;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use PayU\PaymentGateway\Api\RepaymentResolverInterface;

class Action extends Template
{
    const ORDER_ID = 'order_id';
    private RepaymentResolverInterface $repaymentResolver;

    public function __construct(Context $context, RepaymentResolverInterface $repaymentResolver, array $data = [])
    {
        $this->repaymentResolver = $repaymentResolver;
        parent::__construct($context, $data);
    }

    /**
     * Is Any repayment enabled
     */
    public function isPayuCanRepay(): bool
    {
        return $this->repaymentResolver->isAnyRepaymentEnabled();
    }

    /**
     * Is order can repay
     */
    public function isOrderCanRepay(int $orderId): bool
    {
        return $this->repaymentResolver->isRepayment($orderId);
    }

    /**
     * Return url for repay
     */
    public function getOrderRepayUrl(int $orderId): string
    {
        return $this->getUrl('sales/order/repayview', [static::ORDER_ID => $orderId]);
    }
}
