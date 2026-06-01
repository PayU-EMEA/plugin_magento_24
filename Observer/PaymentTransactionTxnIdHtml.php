<?php

namespace PayU\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Sales\Model\Order\Payment\Transaction;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

/**
 * Class PaymentTransactionTxnIdHtml
 * @package PayU\PaymentGateway\Observer
 */
class PaymentTransactionTxnIdHtml extends AbstractDataAssignObserver
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var Transaction $transaction */
        $transaction = $observer->getData('data_object');
        $paymentMethod = $transaction->getOrder()->getPayment()->getMethod();
        $paymentId = $transaction->getAdditionalInformation('payment_id');
        if (PayUSupportedMethods::isSupported($paymentMethod) &&
            $transaction->getTxnType() === 'capture' &&
            $paymentId !== null) {
            $transaction->setData('html_txn_id', $paymentId);
        }
    }
}
