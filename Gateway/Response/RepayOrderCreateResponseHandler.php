<?php

namespace PayU\PaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayUCreateOrderInterface;
use PayU\PaymentGateway\Api\PayURepayOrderInterface;
use PayU\PaymentGateway\Gateway\Helper\RepaySubjectReader;
use PayU\PaymentGateway\Gateway\Validator\AbstractResponseValidator;

class RepayOrderCreateResponseHandler implements HandlerInterface
{
    private PayURepayOrderInterface $payURepayOrder;

    public function __construct(
        PayURepayOrderInterface $payURepayOrder
    )
    {
        $this->payURepayOrder = $payURepayOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $handlingSubject, array $response)
    {
        $order = RepaySubjectReader::readOrder($handlingSubject);
        $method = RepaySubjectReader::readMethod($handlingSubject);
        $payUMethod = RepaySubjectReader::readPayuMethod($handlingSubject);
        $payUMethodType = RepaySubjectReader::readPayuMethodType($handlingSubject);
        $browser = RepaySubjectReader::readPayuBrowser($handlingSubject);

        $this->payURepayOrder->execute($order, $method, $payUMethodType, $payUMethod, $browser, $response['orderId']);
        $payment = $order->getPayment();


        if (array_key_exists(AbstractResponseValidator::VALIDATION_SUBJECT_STATUS, $response)) {
            $payment->setAdditionalInformation(
                PayUConfigInterface::PAYU_RESPONSE_STATUS,
                $response[AbstractResponseValidator::VALIDATION_SUBJECT_STATUS]->statusCode
            );
        }

        if (array_key_exists(PayUConfigInterface::REDIRECT_URI_FIELD, $response)) {
            $payment = $order->getPayment();
            $payment->setAdditionalInformation(
                PayUConfigInterface::PAYU_REDIRECT_URI_CODE,
                $response[PayUConfigInterface::REDIRECT_URI_FIELD]
            );
        }
    }
}
