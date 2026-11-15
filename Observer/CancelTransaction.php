<?php

namespace PayU\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction as TransactionModel;
use Magento\Sales\Api\Data\TransactionInterface;

/**
 * Class CancelTransaction
 * @package PayU\PaymentGateway\Observer
 */
class CancelTransaction implements ObserverInterface
{
    private TransactionRepositoryInterface $transactionRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');
        /** @var Payment $payment */
        $payment = $observer->getData('payment');

        $transactions = $this->getTransactions($order->getEntityId(), $payment->getEntityId());
        foreach ($transactions as $transaction) {
            $transaction->setIsClosed(1);
            $order->addRelatedObject($transaction);
        }
    }

    /**
     * Get all transaction not closed
     *
     * @return TransactionModel[]|TransactionInterface[]
     */
    private function getTransactions(string $orderId, string $paymentId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId, )
            ->addFilter('payment_id', $paymentId)
            ->addFilter('is_closed', 0)
            ->create();

        return $this->transactionRepository->getList($searchCriteria)->getItems();
    }
}
