<?php

namespace PayU\PaymentGateway\Model;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Api\OrderRepositoryInterface;
use PayU\PaymentGateway\Api\OrderPaymentResolverInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayUUpdateOrderStatusInterface;
use PayU\PaymentGateway\Api\WaitingOrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

class WaitingOrderPayment implements WaitingOrderPaymentInterface
{
    private PayUUpdateOrderStatusInterface $updateOrderStatus;

    private PayUConfigInterface $payUConfig;

    private OrderRepositoryInterface $orderRepository;

    private OrderPaymentResolverInterface $paymentResolver;

    public function __construct(
        PayUUpdateOrderStatusInterface $updateOrderStatus,
        PayUConfigInterface $payUConfig,
        OrderRepositoryInterface $orderRepository,
        OrderPaymentResolverInterface $paymentResolver
    ) {
        $this->updateOrderStatus = $updateOrderStatus;
        $this->payUConfig = $payUConfig;
        $this->orderRepository = $orderRepository;
        $this->paymentResolver = $paymentResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(string $txnId): void
    {
        $payment = $this->paymentResolver->getByTransactionTxnId($txnId);
        if ($payment === null) {
            throw new CommandException(__('Payment does not exist'));
        }
        if ($this->payUConfig->isRepaymentActive($payment->getMethod())) {
            $this->processWithRepayment($payment, $txnId);
        } else {
            $this->processWithoutRepayment($payment);
        }
    }

    private function processWithRepayment(Payment $payment, string $orderId): void
    {
        if (!$payment->getData('is_active')) {
            $this->updateOrderStatus->cancel(
                $payment->getMethod(),
                $payment->getOrder()->getStoreId(),
                $orderId
            );
        } else {
            $this->updateOrderStatus->update(
                $payment->getMethod(),
                $payment->getOrder()->getStoreId(),
                $orderId,
                \OpenPayuOrderStatus::STATUS_COMPLETED
            );
        }
    }

    private function processWithoutRepayment(Payment $payment): void
    {
        $order = $payment->getOrder();
        $order->setStatus(Order::STATE_PAYMENT_REVIEW)->setState(Order::STATE_PAYMENT_REVIEW);
        $this->orderRepository->save($order);
    }

}
