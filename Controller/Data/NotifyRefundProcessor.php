<?php

namespace PayU\PaymentGateway\Controller\Data;

use Magento\Payment\Gateway\Command\CommandException;
use PayU\PaymentGateway\Api\PayUUpdateRefundStatusInterface;
use PayU\PaymentGateway\Model\UpdateRefundStatus;

class NotifyRefundProcessor
{
    private UpdateRefundStatus $updateRefundStatus;

    public function __construct(UpdateRefundStatus $updateRefundStatus)
    {
        $this->updateRefundStatus = $updateRefundStatus;
    }

    /**
     * @throws CommandException
     */
    public function process(string $status, string $extOrderId): void
    {
        switch ($status) {
            case PayUUpdateRefundStatusInterface::STATUS_FINALIZED:
                $this->updateRefundStatus->addSuccessMessage($extOrderId);
                break;
            case PayUUpdateRefundStatusInterface::STATUS_CANCELED:
                $this->updateRefundStatus->cancel($extOrderId);
                break;
            default:
                throw new CommandException(__('Unknown Action'));
        }
    }
}
