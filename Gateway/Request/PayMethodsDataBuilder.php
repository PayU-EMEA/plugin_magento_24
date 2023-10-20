<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;

class PayMethodsDataBuilder implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();

        $payMethodType = $payment->getAdditionalInformation(PayUConfigInterface::PAYU_METHOD_TYPE_CODE);
        $payMethodValue = $payment->getAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE);

        if (empty($payMethodType) || empty($payMethodValue)) {
            return [];
        }

        return [
            'body' => [
                'payMethods' => [
                    'payMethod' => [
                        'type' => $payMethodType,
                        'value' => $payMethodValue
                    ]
                ]
            ]
        ];
    }
}
