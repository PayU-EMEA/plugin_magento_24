<?php

namespace PayU\PaymentGateway\Controller\Data;

use PayU\PaymentGateway\Api\AcceptOrderPaymentInterface;
use PayU\PaymentGateway\Api\CancelOrderPaymentInterface;
use PayU\PaymentGateway\Api\WaitingOrderPaymentInterface;
use Magento\Payment\Gateway\Command\CommandException;

class NotifyOrderProcessor
{
    private AcceptOrderPaymentInterface $acceptOrderPayment;
    private CancelOrderPaymentInterface $cancelOrderPayment;
    private WaitingOrderPaymentInterface $waitingOrderPayment;

    public function __construct(
        AcceptOrderPaymentInterface $acceptOrderPayment,
        CancelOrderPaymentInterface $cancelOrderPayment,
        WaitingOrderPaymentInterface $waitingOrderPayment
    ) {
        $this->acceptOrderPayment = $acceptOrderPayment;
        $this->cancelOrderPayment = $cancelOrderPayment;
        $this->waitingOrderPayment = $waitingOrderPayment;
    }

    /**
     * @throws CommandException
     */
    public function process(string $status, string $txnId, int $totalAmount, ?string $paymentId = null): void
    {
        $totalAmount = (float)($totalAmount / 100);
        switch ($status) {
            case \OpenPayuOrderStatus::STATUS_COMPLETED:
                $this->acceptOrderPayment->execute($txnId, $totalAmount, $paymentId);
                break;
            case \OpenPayuOrderStatus::STATUS_CANCELED:
                $this->cancelOrderPayment->execute($txnId, $totalAmount);
                break;
            case \OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION:
            case \OpenPayuOrderStatus::STATUS_REJECTED:
                $this->waitingOrderPayment->execute($txnId, $status);
                break;
            case \OpenPayuOrderStatus::STATUS_PENDING:
                break;
            default:
                throw new CommandException(__('Unknown Action'));
        }
    }
}
