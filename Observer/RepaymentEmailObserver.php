<?php

namespace PayU\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\Logger\Logger;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

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
     * @var Logger
     */
    private $logger;

    /**
     * StatusAssignObserver constructor.
     *
     * @param PayUConfigInterface $payUConfig
     * @param AfterPlaceOrderRepayEmailProcessor $emailProcessor
     * @param Logger $logger
     */
    public function __construct(
        PayUConfigInterface $payUConfig,
        AfterPlaceOrderRepayEmailProcessor $emailProcessor,
        Logger $logger
    ) {
        $this->payUConfig = $payUConfig;
        $this->emailProcessor = $emailProcessor;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');
        $method = $order->getPayment()->getMethod();
        if (!PayUSupportedMethods::isSupported($method)) {
            return;
        }
        if ($this->payUConfig->isRepaymentActive($method)) {
            try {
                $this->emailProcessor->process($order);
            } catch (\Throwable $e) {
                $this->logger->critical($e);
            }
        }
    }
}
