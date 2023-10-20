<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Creditmemo;
use PayU\PaymentGateway\Gateway\Helper\Requests;
use PayU\PaymentGateway\Gateway\Http\Client\PayUAbstractClient;

class RefundRequestBuilder implements BuilderInterface
{

    private Requests $payuRequests;

    public function __construct(
        Requests $payuRequests
    )
    {
        $this->payuRequests = $payuRequests;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();

        $creditMemo = $payment->getCreditmemo();

        return [
            'body' => [
                PayUAbstractClient::ORDER_ID => $this->getTxnId($payment->getLastTransId()),
                PayUAbstractClient::DESCRIPTION => $this->getLastCreditMemoComment($creditMemo, $order->getOrderIncrementId()),
                PayUAbstractClient::AMOUNT => $this->payuRequests->formatAmount($creditMemo->getGrandTotal())
            ]
        ];
    }

    private function getTxnId(string $transId): string
    {
        return str_replace('-' . TransactionInterface::TYPE_CAPTURE, '', $transId);
    }

    private function getLastCreditMemoComment(Creditmemo $creditMemo, string $orderId): string
    {
        $comments = $creditMemo->getComments();
        if ($comments) {
            foreach ($comments as $comment) {
                if ($comment->isObjectNew() && !empty($comment->getComment())) {
                    return $comment->getComment();
                }
            }
        }

        return (string)__('Refund for order %1', $orderId);
    }
}
