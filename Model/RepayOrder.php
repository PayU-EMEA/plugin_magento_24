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
        $newPayment = $this->makeNewPayment(
            $payment,
            $order->getEntityId(),
            $method,
            $payUMethod,
            $payUMethodType,
            $payuBrowser,
            $transactionId
        );
        $this->addPaymentToOrder($order, $newPayment, $transactionId);
        $this->addTransactionToPayment($newPayment, $order->getEntityId(), $transactionId);
    }

    private function addPaymentToOrder(OrderInterface $order, OrderPaymentInterface $payment, string $transactionId): void
    {
        $order->setPayment($payment);
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

    private function makeNewPayment(OrderPaymentInterface $payment, int $orderId, string $method, string $payUMethodType, string $payUMethod, array $payuBrowser, string $transactionId): OrderPaymentInterface
    {
        $newPayment = $this->paymentRepository->create();
        $newPayment->setMethod($method);
        $newPayment->setParentId($orderId);
        $newPayment->setBaseAmountAuthorized($payment->getBaseAmountAuthorized());
        $newPayment->setBaseShippingAmount($payment->getBaseShippingAmount());
        $newPayment->setShippingAmount($payment->getShippingAmount());
        $newPayment->setAmountAuthorized($payment->getAmountAuthorized());
        $newPayment->setBaseAmountOrdered($payment->getBaseAmountOrdered());
        $newPayment->setAmountOrdered($payment->getAmountOrdered());
        $newPayment->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE, $payUMethod);
        $newPayment->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_TYPE_CODE, $payUMethodType);
        $newPayment->setAdditionalInformation('method_title', 'PayU');
        $newPayment->setAdditionalInformation('payu_browser', $payuBrowser);
        $newPayment->setLastTransId($transactionId);

        return $this->paymentRepository->save($newPayment);
    }
}
