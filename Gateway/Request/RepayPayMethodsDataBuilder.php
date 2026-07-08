<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Gateway\Helper\RepaySubjectReader;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

class RepayPayMethodsDataBuilder implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $methodCode = RepaySubjectReader::readMethod($buildSubject);
        $payuMethod = RepaySubjectReader::readPayuMethod($buildSubject);
        $payuMethodType = RepaySubjectReader::readPayuMethodType($buildSubject);

        if ($methodCode === PayUSupportedMethods::CODE_GOOGLE_PAY) {
            return $this->buildGooglePayData(RepaySubjectReader::readPayuAuthorizationCode($buildSubject));
        }

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

    private function buildGooglePayData(string $authorizationCode): array
    {
        if (empty(trim($authorizationCode))) {
            return [];
        }

        return [
            'body' => [
                'payMethods' => [
                    'payMethod' => [
                        'type' => PayUConfigInterface::PAYU_BANK_TRANSFER_KEY,
                        'value' => PayUConfigInterface::PAYU_GOOGLE_PAY_METHOD_VALUE,
                        'authorizationCode' => trim($authorizationCode),
                    ]
                ]
            ]
        ];
    }
}
