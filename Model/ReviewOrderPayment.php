<?php

namespace PayU\PaymentGateway\Model;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use PayU\PaymentGateway\Api\OrderPaymentResolverInterface;
use PayU\PaymentGateway\Api\PayUUpdateOrderStatusInterface;
use PayU\PaymentGateway\Api\ReviewOrderPaymentInterface;

class ReviewOrderPayment implements ReviewOrderPaymentInterface
{
    /**
     * Error Message
     */
    const REVIEW_ERROR = 'We can\'t update the payment right now.';

    private Payment $payment;
    private PayUUpdateOrderStatusInterface $updateOrderStatus;
    private EventManager $eventManager;
    private OrderPaymentResolverInterface $paymentResolver;

    public function __construct(
        PayUUpdateOrderStatusInterface $updateOrderStatus,
        EventManager                   $eventManager,
        OrderPaymentResolverInterface  $paymentResolver
    )
    {
        $this->updateOrderStatus = $updateOrderStatus;
        $this->eventManager = $eventManager;
        $this->paymentResolver = $paymentResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(OrderInterface $order, string $action): void
    {
        $this->payment = $this->paymentResolver->getLast($order);
        $method = $action . 'Payment';
        if (!method_exists($this, $method)) {
            throw new LocalizedException(__('Action "%1" is not supported.', $action));
        }

        $this->{$method}($order->getStoreId());
    }

    /**
     * Accept Payment Action
     *
     * @throws LocalizedException|\OpenPayU_Exception
     */
    protected function acceptPayment(int $storeId): void
    {
        $response = $this->updateOrderStatus->update(
            $this->payment->getMethod(),
            $storeId,
            $this->payment->getLastTransId(),
            \OpenPayuOrderStatus::STATUS_COMPLETED
        );
        if ($response->getStatus() === \OpenPayU_Order::SUCCESS) {
            $this->eventManager->dispatch('payu_payment_status_assign', ['payment' => $this->payment]);
        } else {
            throw new LocalizedException(__(static::REVIEW_ERROR));
        }
    }

    /**
     * Deny Payment Action
     *
     * @throws LocalizedException|\OpenPayU_Exception
     */
    protected function denyPayment(int $storeId): void
    {
        $response = $this->updateOrderStatus->cancel(
            $this->payment->getMethod(),
            $storeId,
            $this->payment->getLastTransId()

        );
        if ($response !== null && $response->getStatus() === \OpenPayU_Order::SUCCESS) {
            $this->eventManager->dispatch('payu_payment_status_assign', ['payment' => $this->payment]);
        } else {
            throw new LocalizedException(__(static::REVIEW_ERROR));
        }
    }
}
