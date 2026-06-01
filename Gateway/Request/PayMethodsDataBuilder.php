<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

class PayMethodsDataBuilder implements BuilderInterface
{

    private const PAY_METHOD_CONFIGURATION = [
        'PLN' => [
            PayUSupportedMethods::CODE_INSTALLMENTS => 'ai',
            PayUSupportedMethods::CODE_KLARNA => 'dpkl',
            PayUSupportedMethods::CODE_PAYPO => 'dpp',
            PayUSupportedMethods::CODE_PRAGMA => 'ppf',
            PayUSupportedMethods::CODE_TWISTO => 'dpt',
        ],
        'CZK' => [
            PayUSupportedMethods::CODE_KLARNA => 'dpklczk',
            PayUSupportedMethods::CODE_TWISTO => 'dpcz',
            PayUSupportedMethods::CODE_TWISTO_SLICE => 'dpts',
        ],
        'RON' => [
            PayUSupportedMethods::CODE_KLARNA => 'dpklron',
            PayUSupportedMethods::CODE_PAYPO => 'dppron',
        ],
        'EUR' => [
            PayUSupportedMethods::CODE_KLARNA => 'dpkleur',
        ],
        'HUF' => [
            PayUSupportedMethods::CODE_KLARNA => 'dpklhuf',
        ],
    ];

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $methodCode = $payment->getMethodInstance()->getCode();

        $payMethodType = null;
        $payMethodValue = null;

        if (in_array($methodCode, [PayUSupportedMethods::CODE_GATEWAY, PayUSupportedMethods::CODE_CARD], true)) {
            $payMethodType = $payment->getAdditionalInformation(PayUConfigInterface::PAYU_METHOD_TYPE_CODE);
            $payMethodValue = $payment->getAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE);
        }

        if (empty($payMethodType) || empty($payMethodValue)) {
            $currencyCode = $payment->getOrder()->getOrderCurrencyCode();

            if (array_key_exists($currencyCode, self::PAY_METHOD_CONFIGURATION)) {
                $payMethodConfig = self::PAY_METHOD_CONFIGURATION[$currencyCode];

                if (array_key_exists($methodCode, $payMethodConfig)) {
                    $payMethodType = PayUConfigInterface::PAYU_BANK_TRANSFER_KEY;
                    $payMethodValue = $payMethodConfig[$methodCode];
                }
            }
        }

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
