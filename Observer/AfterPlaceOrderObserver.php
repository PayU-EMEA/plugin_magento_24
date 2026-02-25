<?php

namespace PayU\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\PayUSupportedMethods;
use Magento\Sales\Model\Order\Payment;

/**
 * Class AfterPlaceOrderObserver
 * @package PayU\PaymentGateway\Observer
 */
class AfterPlaceOrderObserver implements ObserverInterface
{
    /**
     * Status pending
     */
    const STATUS_PENDING = 'pending';

    /**
     * @var PayUConfigInterface
     */
    private $payUConfig;

    /**
     * StatusAssignObserver constructor.
     *
     * @param PayUConfigInterface $payUConfig
     */
    public function __construct(
        PayUConfigInterface $payUConfig
    ) {
        $this->payUConfig = $payUConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var Payment $payment */
        $payment = $observer->getData('payment');
        $method = $payment->getMethod();
        if (!PayUSupportedMethods::isSupported($method)) {
            return;
        }
        $this->assignStatus($payment);
    }

    /**
     * @param Payment $payment
     *
     * @return void
     */
    private function assignStatus(Payment $payment)
    {
        $order = $payment->getOrder();
        $order->setState(Order::STATE_NEW)->setStatus(static::STATUS_PENDING);
    }
}
