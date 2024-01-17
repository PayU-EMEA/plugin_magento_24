<?php

namespace PayU\PaymentGateway\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\RepaymentResolverInterface;
use PayU\PaymentGateway\Model\Ui\CardConfigProvider;
use PayU\PaymentGateway\Model\Ui\ConfigProvider;
use PayU\PaymentGateway\Observer\AfterPlaceOrderObserver;

class RepaymentResolver implements RepaymentResolverInterface
{
    private PayUConfigInterface $payUConfig;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(PayUConfigInterface $payUConfig, OrderRepositoryInterface $orderRepository)
    {
        $this->payUConfig = $payUConfig;
        $this->orderRepository = $orderRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isRepayment(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();

        return in_array($payment->getMethod(), [ConfigProvider::CODE, CardConfigProvider::CODE]) &&
            $order->getStatus() === AfterPlaceOrderObserver::STATUS_PENDING &&
            $this->payUConfig->isRepaymentActive($payment->getMethod());
    }

    /**
     * {@inheritdoc}
     */
    public function isAnyRepaymentEnabled(): bool
    {
        return $this->payUConfig->isRepaymentActive(ConfigProvider::CODE)
            || $this->payUConfig->isRepaymentActive(CardConfigProvider::CODE);
    }
}
