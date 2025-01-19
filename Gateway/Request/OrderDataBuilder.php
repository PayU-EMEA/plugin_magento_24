<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Gateway\Helper\Requests;

class OrderDataBuilder implements BuilderInterface
{
    private UrlInterface $urlBuilder;
    private Requests $payuRequests;

    private PayUConfigInterface $payUConfig;

    public function __construct(
        UrlInterface $urlBuilder,
        Requests $payuRequests,
        PayUConfigInterface $payUConfig

    ) {
        $this->urlBuilder   = $urlBuilder;
        $this->payuRequests = $payuRequests;
        $this->payUConfig   = $payUConfig;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order             = $paymentDataObject->getOrder();
        $payment           = $paymentDataObject->getPayment();

        $body = [
            'body' => [
                'description' => $this->getOrderDescription($order),
                'customerIp'  => $this->payuRequests->getIp(),
                'extOrderId'  => $this->getExtOrderId($order),
                'notifyUrl'   => $this->urlBuilder->getUrl('payu/data/getNotify', [
                    'type'  => $payment->getMethodInstance()->getCode(),
                    'store' => $order->getStoreId()
                ]),
                'continueUrl' => $this->urlBuilder->getUrl('checkout/onepage/success'),
            ]
        ];

        if ($this->payUConfig->isCancelOrder($payment->getMethod())) {
            $body['body']['settings']['allowOrderCancellationByPayer'] = true;
            $body['body']['cancelUrl']                                 = $this->urlBuilder->getUrl('payu/order/cancel');
        }

        return $body;
    }

    private function getOrderDescription(OrderAdapterInterface $order): string
    {
        $shopUrl = str_replace('www.', '', parse_url($this->urlBuilder->getBaseUrl(), PHP_URL_HOST));

        return __('Order %1 [%2]', $order->getOrderIncrementId(), $shopUrl)->render();
    }

    private function getExtOrderId($order): string
    {
        return uniqid($order->getOrderIncrementId() . '_');
    }
}
