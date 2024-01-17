<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Gateway\Helper\RepaySubjectReader;

class RepayPayMethodsDataBuilder implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $payuMethod = RepaySubjectReader::readPayuMethod($buildSubject);
        $payuMethodType = RepaySubjectReader::readPayuMethodType($buildSubject);

        if (empty($payuMethod) || empty($payuMethodType)) {
            return [];
        }

        return [
            'body' => [
                'payMethods' => [
                    'payMethod' => [
                        'type' => $payuMethodType,
                        'value' => $payuMethod
                    ]
                ]
            ]
        ];
    }
}
