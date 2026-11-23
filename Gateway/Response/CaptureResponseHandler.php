<?php

namespace PayU\PaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\Logger\Logger;

/**
 * Stores on the payment the pay method actually used by the buyer on the PayU side,
 * so it can be displayed on the order (admin, customer account, e-mails).
 */
class CaptureResponseHandler implements HandlerInterface
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = SubjectReader::readPayment($handlingSubject)->getPayment();
        $orderId = $payment->getAdditionalInformation('completed_order_id');
        if (empty($orderId)) {
            return;
        }

        try {
            $result = json_decode(
                json_encode(\OpenPayU_Order::retrieveTransaction($orderId)->getResponse()),
                true
            );
        } catch (\Exception $e) {
            // The capture itself succeeded: missing display data must not break it
            $this->logger->warning(
                'Unable to retrieve the PayU transaction data: ' . $e->getMessage(),
                ['orderId' => $orderId]
            );

            return;
        }

        $transaction = is_array($result) ? ($result['transactions'][0] ?? []) : [];
        $cardData = $transaction['card']['cardData'] ?? [];

        $data = [
            PayUConfigInterface::PAYU_TRANSACTION_PAY_METHOD => $transaction['payMethod']['value'] ?? null,
            PayUConfigInterface::PAYU_TRANSACTION_CARD_SCHEME => $cardData['cardScheme'] ?? null,
            PayUConfigInterface::PAYU_TRANSACTION_CARD_NUMBER_MASKED => $cardData['cardNumberMasked'] ?? null,
        ];

        foreach ($data as $key => $value) {
            if (is_string($value) && $value !== '') {
                $payment->setAdditionalInformation($key, $value);
            }
        }
    }
}
