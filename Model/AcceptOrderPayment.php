<?php

namespace PayU\PaymentGateway\Model;

use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface as TransactionManager;
use PayU\PaymentGateway\Api\AcceptOrderPaymentInterface;
use PayU\PaymentGateway\Api\OrderPaymentResolverInterface;

/**
 * Class NotifyOrderResolver
 * @package PayU\PaymentGateway\Model
 */
class AcceptOrderPayment implements AcceptOrderPaymentInterface
{
    private EventManager $eventManager;
    private Transaction $transaction;
    private OrderPaymentResolverInterface $paymentResolver;
    private TransactionManager $transactionManager;

    public function __construct(
        EventManager                  $eventManager,
        Transaction                   $transaction,
        OrderPaymentResolverInterface $paymentResolver,
        TransactionManager            $transactionManager
    )
    {
        $this->eventManager = $eventManager;
        $this->transaction = $transaction;
        $this->paymentResolver = $paymentResolver;
        $this->transactionManager = $transactionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(string $txnId, float $amount, ?string $paymentId = null): void
    {
        $payment = $this->paymentResolver->getByTransactionTxnId($txnId);
        if ($payment === null) {
            throw new CommandException(__('Payment does not exist'));
        }
        if (!$payment->canCapture()) {
            if ($this->isAdditionalPayment($payment, $txnId)) {
                $this->addAdditionalPayment($payment, $txnId, $amount, $paymentId);

                return;
            }
            throw new CommandException(__('Payment could not be captured'));
        }
        if ($paymentId !== null) {
            $payment->setTransactionAdditionalInfo('payment_id', $paymentId);
        }
        $payment->setAdditionalInformation('completed_order_id', $txnId);
        $payment->setParentTransactionId($txnId);
        $payment->capture();
        $order = $payment->getOrder();
        $eventData = ['order' => $order, 'payment' => $payment];
        $this->eventManager->dispatch('payu_accept_order_payment', $eventData);
        $this->eventManager->dispatch('payu_close_repayment_transaction', $eventData);
        $this->transaction->addObject($payment);
        $this->transaction->addObject($order);
        $this->transaction->save();
    }

    /**
     * A repaid order keeps both PayU links payable, so the second one can complete after the order is invoiced
     */
    private function isAdditionalPayment(Payment $payment, string $txnId): bool
    {
        $completedOrderId = $payment->getAdditionalInformation('completed_order_id');

        return $completedOrderId !== null
            && $completedOrderId !== $txnId
            && !$payment->getOrder()->canInvoice();
    }

    private function addAdditionalPayment(Payment $payment, string $txnId, float $amount, ?string $paymentId): void
    {
        $order = $payment->getOrder();
        $captureTxnId = $txnId . '-' . TransactionInterface::TYPE_CAPTURE;
        if ($this->transactionManager->isTransactionExists($captureTxnId, $payment->getId(), $order->getId())) {
            return;
        }
        if ($paymentId !== null) {
            $payment->setTransactionAdditionalInfo('payment_id', $paymentId);
        }
        $lastTransId = $payment->getLastTransId();
        $payment->setTransactionId($captureTxnId);
        $payment->setParentTransactionId($txnId);
        $transaction = $payment->addTransaction(TransactionInterface::TYPE_CAPTURE);
        // refunds use the last transaction id, which has to stay on the invoiced PayU order
        $payment->setLastTransId($lastTransId);
        $payment->addTransactionCommentsToOrder(
            $transaction,
            __(
                'Additional PayU payment of %1 received. It is not invoiced, refund it in the PayU panel if it is not intended.',
                $order->formatPriceTxt($amount)
            )
        );
        $this->transaction->addObject($payment);
        $this->transaction->addObject($order);
        $this->transaction->save();
    }
}
