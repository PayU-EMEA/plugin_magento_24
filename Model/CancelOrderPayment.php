<?php

namespace PayU\PaymentGateway\Model;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesRule\Model\Coupon\UpdateCouponUsages;
use PayU\PaymentGateway\Api\CancelOrderPaymentInterface;
use PayU\PaymentGateway\Api\OrderPaymentResolverInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;

class CancelOrderPayment implements CancelOrderPaymentInterface
{
    private OrderRepositoryInterface $orderRepository;
    private OrderPaymentResolverInterface $orderPaymentResolver;
    private PayUConfigInterface $payUConfig;
    private UpdateCouponUsages $updateCouponUsages;
    private EventManager $eventManager;

    public function __construct(
        OrderRepositoryInterface       $orderRepository,
        OrderPaymentResolverInterface  $orderPaymentResolver,
        UpdateCouponUsages             $updateCouponUsages,
        PayUConfigInterface            $payUConfig,
        EventManager                   $eventManager
    )
    {
        $this->orderRepository = $orderRepository;
        $this->orderPaymentResolver = $orderPaymentResolver;
        $this->updateCouponUsages = $updateCouponUsages;
        $this->payUConfig = $payUConfig;
        $this->eventManager = $eventManager;
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
        if ($order->canCancel()
            && $amount == $payment->getAmountAuthorized()
            && !$this->payUConfig->isRepaymentActive($payment->getMethod())
        ) {
            $order->cancel();
            $this->orderRepository->save($order);
            $this->updateCouponUsages->execute($order, false);
            $this->eventManager->dispatch(
                'payu_close_repayment_transaction',
                ['order' => $payment->getOrder(), 'payment' => $payment]
            );
        }
    }
}
