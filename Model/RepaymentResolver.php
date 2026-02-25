<?php

namespace PayU\PaymentGateway\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\RepaymentResolverInterface;
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
        $paymentMethod = $payment->getMethod();

        return PayUSupportedMethods::isSupported($paymentMethod) &&
            $order->getStatus() === AfterPlaceOrderObserver::STATUS_PENDING &&
            $this->payUConfig->isRepaymentActive($paymentMethod);
    }

    /**
     * {@inheritdoc}
     */
    public function isAnyRepaymentEnabled(): bool
    {
        return $this->payUConfig->isRepaymentActive(PayUSupportedMethods::CODE_GATEWAY)
            || $this->payUConfig->isRepaymentActive(PayUSupportedMethods::CODE_CARD)
            || $this->payUConfig->isRepaymentActive(PayUSupportedMethods::CODE_INSTALLMENTS)
            || $this->payUConfig->isRepaymentActive(PayUSupportedMethods::CODE_KLARNA)
            || $this->payUConfig->isRepaymentActive(PayUSupportedMethods::CODE_PAYPO)
            || $this->payUConfig->isRepaymentActive(PayUSupportedMethods::CODE_PRAGMA)
            || $this->payUConfig->isRepaymentActive(PayUSupportedMethods::CODE_TWISTO)
            || $this->payUConfig->isRepaymentActive(PayUSupportedMethods::CODE_TWISTO_SLICE);
    }
}
