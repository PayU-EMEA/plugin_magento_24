<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Gateway\Helper\Requests;

class ThreeDsAuthenticationDataBuilder implements BuilderInterface
{
    const THREE_DS_PAY_METHODS = ['c', 'ma', 'vc', 'jp', 'ap'];
    private Requests $payuRequests;

    public function __construct(
        Requests $payuRequests
    )
    {
        $this->payuRequests = $payuRequests;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $paymentDataObject->getOrder();

        $payMethodType = $payment->getAdditionalInformation(PayUConfigInterface::PAYU_METHOD_TYPE_CODE);
        $payMethodValue = $payment->getAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE);

        $billingAddress = $order->getBillingAddress();

        $threeDsAuthentication = [];

        if ($billingAddress !== null &&
            (empty($payMethodType) || empty($payMethodValue)
                || $payMethodType === 'CARD_TOKEN'
                || in_array($payMethodValue, self::THREE_DS_PAY_METHODS)
            )
        ) {

            $name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
            $address = $billingAddress->getStreetLine1() . ($billingAddress->getStreetLine2() ? ' ' . $billingAddress->getStreetLine2() : '');
            $postalCode = $billingAddress->getPostcode();
            $city = $billingAddress->getCity();
            $countryCode = $billingAddress->getCountryId();

            $isBillingAddress = !empty($address) || !empty($postalCode) || !empty($city) || (!empty($countryCode) && strlen($countryCode) === 2);

            if (!empty($name) || $isBillingAddress) {
                $threeDsAuthentication['cardholder'] = [];

                if (!empty($name)) {
                    $threeDsAuthentication['cardholder']['name'] = mb_substr($name, 0, 50);
                }

                if ($isBillingAddress) {
                    $threeDsAuthentication['cardholder']['billingAddress'] = [];
                }

                if (!empty($countryCode) && strlen($countryCode) === 2) {
                    $threeDsAuthentication['cardholder']['billingAddress']['countryCode'] = $countryCode;
                }

                if (!empty($address)) {
                    $threeDsAuthentication['cardholder']['billingAddress']['street'] = mb_substr($address, 0, 50);
                }

                if (!empty($city)) {
                    $threeDsAuthentication['cardholder']['billingAddress']['city'] = mb_substr($city, 0, 50);
                }

                if (!empty($postalCode)) {
                    $threeDsAuthentication['cardholder']['billingAddress']['postalCode'] = mb_substr($postalCode, 0, 16);
                }
            }

            if ($payMethodType === 'CARD_TOKEN') {
                $browserData = [
                    'requestIP' => $this->payuRequests->getIp()
                ];

                foreach (PayUConfigInterface::PAYU_BROWSER as $bd) {
                    $browserData[$bd] = $payment->getAdditionalInformation(PayUConfigInterface::PAYU_BROWSER_PREFIX . $bd) ?? '';
                }

                if (empty($browserData['userAgent'])) {
                    if ($_SERVER['HTTP_USER_AGENT']) {
                        $browserData['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
                    }
                }

                $threeDsAuthentication['browser'] = $browserData;
            }
        }

        return empty($threeDsAuthentication) ? [] : [
            'body' => [
                'threeDsAuthentication' => $threeDsAuthentication
            ]
        ];
    }
}
