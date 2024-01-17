<?php

namespace PayU\PaymentGateway\Model;

use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use PayU\PaymentGateway\Api\OrderByExtOrderIdResolverInterface;
use PayU\PaymentGateway\Api\PayUUpdateRefundStatusInterface;

class UpdateRefundStatus implements PayUUpdateRefundStatusInterface
{
    private OrderByExtOrderIdResolverInterface $extOrderIdResolver;
    private Transaction $transaction;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        OrderByExtOrderIdResolverInterface $extOrderIdResolver,
        Transaction                        $transaction,
        OrderRepositoryInterface           $orderRepository
    )
    {
        $this->extOrderIdResolver = $extOrderIdResolver;
        $this->transaction = $transaction;
        $this->orderRepository = $orderRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(string $extOrderId): void
    {
        $orderByIncrementId = $this->extOrderIdResolver->resolve($extOrderId);
        $orderByIncrementId->addCommentToStatusHistory(__('Refund was canceled.'));
        /** @var Creditmemo $creditMemo */
        $creditMemo = $orderByIncrementId->getCreditmemosCollection()->getLastItem();
        $creditMemo->setState(Creditmemo::STATE_CANCELED)->setCreditmemoStatus(Creditmemo::STATE_CANCELED);
        $this->transaction->addObject($creditMemo);
        $this->transaction->addObject($orderByIncrementId);
        $this->transaction->save();
    }

    /**
     * {@inheritdoc}
     */
    public function addSuccessMessage(string $extOrderId): void
    {
        $orderByIncrementId = $this->extOrderIdResolver->resolve($extOrderId);
        $orderByIncrementId->addCommentToStatusHistory(__('Refund was finalized.'));
        if ($orderByIncrementId->getTotalRefunded() === $orderByIncrementId->getTotalPaid()) {
            $orderByIncrementId->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED);
        }
        $this->orderRepository->save($orderByIncrementId);
    }
}
