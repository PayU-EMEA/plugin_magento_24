<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use PayU\PaymentGateway\Gateway\Helper\RepaySubjectReader;
use PayU\PaymentGateway\Gateway\Helper\Requests;

class RepayOrderDataBuilder implements BuilderInterface
{
    private UrlInterface $urlBuilder;
    private Requests $payuRequests;

    public function __construct(
        UrlInterface $urlBuilder,
        Requests     $payuRequests

    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->payuRequests = $payuRequests;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $order = RepaySubjectReader::readOrder($buildSubject);
        $method = RepaySubjectReader::readMethod($buildSubject);

        return [
            'body' => [
                'description' => $this->getOrderDescription($order),
                'customerIp' => $this->payuRequests->getIp(),
                'extOrderId' => $this->getExtOrderId($order),
                'notifyUrl' => $this->urlBuilder->getUrl('payu/data/getNotify', [
                    'type' => $method,
                    'store' => $order->getStoreId()
                ]),
                'continueUrl' => $this->urlBuilder->getUrl('sales/order/history'),
            ]
        ];
    }

    private function getOrderDescription(Order $order): string
    {
        $shopUrl = str_replace('www.', '', parse_url($this->urlBuilder->getBaseUrl(), PHP_URL_HOST));

        return __('Order %1 [%2]', $order->getIncrementId(), $shopUrl)->render();
    }

    private function getExtOrderId(Order $order): string
    {
        return uniqid($order->getIncrementId() . '_');
    }
}
