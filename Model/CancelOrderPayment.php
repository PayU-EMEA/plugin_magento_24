<?php

namespace PayU\PaymentGateway\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DB\Transaction;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction as TransactionModel;
use Magento\SalesRule\Model\Coupon\UpdateCouponUsages;
use PayU\PaymentGateway\Api\CancelOrderPaymentInterface;
use PayU\PaymentGateway\Api\OrderPaymentResolverInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;

class CancelOrderPayment implements CancelOrderPaymentInterface
{
    private OrderRepositoryInterface $orderRepository;
    private OrderPaymentResolverInterface $orderPaymentResolver;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private TransactionRepositoryInterface $transactionRepository;
    private Transaction $transaction;
    private PayUConfigInterface $payUConfig;
    private UpdateCouponUsages $updateCouponUsages;

    public function __construct(
        OrderRepositoryInterface       $orderRepository,
        OrderPaymentResolverInterface  $orderPaymentResolver,
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        TransactionRepositoryInterface $transactionRepository,
        Transaction                    $transaction,
        UpdateCouponUsages             $updateCouponUsages,
        PayUConfigInterface            $payUConfig
    )
    {
        $this->orderRepository = $orderRepository;
        $this->orderPaymentResolver = $orderPaymentResolver;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->transaction = $transaction;
        $this->updateCouponUsages = $updateCouponUsages;
        $this->payUConfig = $payUConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(string $txnId, float $amount): void
    {
        $payment = $this->orderPaymentResolver->getByTransactionTxnId($txnId);
        if ($payment === null) {
            throw new CommandException(__('Payment does not exist'));
        }
        $order = $payment->getOrder();
        if ($order->canCancel() && $amount == $payment->getAmountAuthorized()) {
            $this->closeTransactions($order->getEntityId(), $payment->getEntityId());
            if ($this->getAllActiveTransaction($order->getEntityId()) === 0 &&
                !$this->payUConfig->isRepaymentActive($payment->getMethod())) {
                $order->cancel();
                $this->orderRepository->save($order);
                $this->updateCouponUsages->execute($order, false);
            }
        }
    }

    private function closeTransactions(string $orderId, string $paymentId): void
    {
        $searchCriteria = $this->getSearchCriteriaForOrder($orderId)->addFilter(
            'payment_id',
            $paymentId,
            'eq'
        )->create();

        /** @var TransactionModel[]|TransactionInterface[] $transactions */
        $transactions = $this->transactionRepository->getList($searchCriteria)->getItems();
        foreach ($transactions as $transaction) {
            $transaction->setIsClosed(1);
            $this->transaction->addObject($transaction);
        }
        $this->transaction->save();
    }

    private function getAllActiveTransaction(string $orderId): int
    {
        $searchCriteria = $this->getSearchCriteriaForOrder($orderId)->addFilter(
            'is_closed',
            0,
            'eq'
        )->create();

        return count($this->transactionRepository->getList($searchCriteria)->getItems());
    }

    /**
     * Set Search Criteria for Order ID
     */
    private function getSearchCriteriaForOrder(string $orderId): SearchCriteriaBuilder
    {
        $this->searchCriteriaBuilder->addFilter('order_id', $orderId, 'eq');

        return $this->searchCriteriaBuilder;
    }
}
