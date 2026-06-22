<?php

namespace PayU\PaymentGateway\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\Data\TransactionInterfaceFactory;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayURepayOrderInterface;

/**
 * Class RepayOrder
 * @package PayU\PaymentGateway\Model
 */
class RepayOrder implements PayURepayOrderInterface
{
    private TransactionInterfaceFactory $transactionFactory;
    private TransactionRepositoryInterface $transactionRepository;
    private OrderPaymentRepositoryInterface $paymentRepository;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        TransactionInterfaceFactory     $transactionFactory,
        TransactionRepositoryInterface  $transactionRepository,
        OrderPaymentRepositoryInterface $paymentRepository,
        OrderRepositoryInterface        $orderRepository
    )
    {
        $this->transactionFactory = $transactionFactory;
        $this->transactionRepository = $transactionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
    }

    public function execute(OrderInterface $order, string $method, string $payUMethodType, string $payUMethod, array $payuBrowser, string $transactionId): void
    {
        $payment = $order->getPayment();

        $this->updatePayment(
            $payment,
            $method,
            $payUMethod,
            $payUMethodType,
            $payuBrowser,
            $transactionId
        );

        $this->updateOrder($order, $payment, $transactionId);
        $this->addTransactionToPayment($payment, $order->getEntityId(), $transactionId);
    }

    private function updateOrder(OrderInterface $order, OrderPaymentInterface $payment, string $transactionId): void
    {
        $order->addCommentToStatusHistory(
            __(
                'Authorized amount of %1. Transaction ID: "%2"',
                $payment->formatPrice($payment->getAmountAuthorized()),
                $transactionId
            )
        )->setIsCustomerNotified(
            false
        );
        $this->orderRepository->save($order);
    }

    private function addTransactionToPayment(OrderPaymentInterface $payment, int $orderId, string $transactionId): void
    {
        $paymentTransaction = $this->transactionFactory->create();
        $paymentTransaction->setOrderId($orderId);
        $paymentTransaction->setPaymentId($payment->getEntityId());
        $paymentTransaction->setTxnId($transactionId);
        $paymentTransaction->setTxnType(TransactionInterface::TYPE_AUTH);
        $paymentTransaction->setIsClosed(0);
        $this->transactionRepository->save($paymentTransaction);
    }

    private function updatePayment(OrderPaymentInterface $payment, string $method, string $payUMethodType, string $payUMethod, array $payuBrowser, string $transactionId): void
    {
        $payment->setMethod($method);
        $payment->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE, $payUMethod);
        $payment->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_TYPE_CODE, $payUMethodType);
        $payment->setAdditionalInformation('method_title', 'PayU');
        $payment->setAdditionalInformation('payu_browser', $payuBrowser);
        $payment->setLastTransId($transactionId);

        $this->paymentRepository->save($payment);
    }
}
