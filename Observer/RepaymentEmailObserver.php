<?php

namespace PayU\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\Ui\CardConfigProvider;
use PayU\PaymentGateway\Model\Ui\ConfigProvider;
use Magento\Sales\Model\Order\Payment;

/**
 * Class RepaymentEmailObserver
 * @package PayU\PaymentGateway\Observer
 */
class RepaymentEmailObserver implements ObserverInterface
{
    /**
     * @var PayUConfigInterface
     */
    private $payUConfig;

    /**
     * @var AfterPlaceOrderRepayEmailProcessor
     */
    private $emailProcessor;

    /**
     * StatusAssignObserver constructor.
     *
     * @param PayUConfigInterface $payUConfig
     * @param AfterPlaceOrderRepayEmailProcessor $emailProcessor
     */
    public function __construct(
        PayUConfigInterface $payUConfig,
        AfterPlaceOrderRepayEmailProcessor $emailProcessor
    ) {
        $this->payUConfig = $payUConfig;
        $this->emailProcessor = $emailProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');
        $method = $order->getPayment()->getMethod();
        if ($method !== CardConfigProvider::CODE && $method !== ConfigProvider::CODE) {
            return;
        }
        if ($this->payUConfig->isRepaymentActive($method)) {
            $this->emailProcessor->process($order);
        }
    }
}
