<?php

namespace PayU\PaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;

class OrderCreateResponseHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDataObject->getPayment();

        $payment
            ->setTransactionId($response['orderId'])
            ->setIsTransactionPending(true)
            ->setIsTransactionClosed(false);

        if (array_key_exists(PayUConfigInterface::REDIRECT_URI_FIELD, $response)) {
            $payment->setAdditionalInformation(
                PayUConfigInterface::PAYU_REDIRECT_URI_CODE,
                $response[PayUConfigInterface::REDIRECT_URI_FIELD]
            );
        }
    }
}
