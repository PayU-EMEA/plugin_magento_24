<?php

namespace PayU\PaymentGateway\Api;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Interface CreateOrderResolverInterface
 * @package PayU\PaymentGateway\Api
 */
interface CreateOrderResolverInterface
{
    /**
     * Get data for create order in PayU REST API
     *
     * @param OrderAdapterInterface $order
     * @param Payment $order
     * @param string $methodTypeCode
     * @param string $methodCode
     * @param array $browser
     * @param null|float $totalDue
     * @param null|float $orderCurrencyCode
     * @param string $continueUrl
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function resolve(
        OrderAdapterInterface $order,
        Payment $payment,
        $methodTypeCode,
        $methodCode,
        $browser,
        $totalDue = null,
        $orderCurrencyCode = null,
        $continueUrl = 'checkout/onepage/success'
    );
}
