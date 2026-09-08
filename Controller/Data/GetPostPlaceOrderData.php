<?php

namespace PayU\PaymentGateway\Controller\Data;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\OrderRepository;
use PayU\PaymentGateway\Api\PayUConfigInterface;

class GetPostPlaceOrderData implements HttpGetActionInterface
{
    const SUCCESS_FIELD = 'success';

    private CustomerSession $customerSession;
    private ResultFactory $resultFactory;
    private UrlInterface $url;
    private RequestInterface $request;
    private OrderRepository $orderRepository;

    public function __construct(
        ResultFactory    $resultFactory,
        CustomerSession  $customerSession,
        UrlInterface     $url,
        RequestInterface $request,
        OrderRepository  $orderRepository
    )
    {
        $this->resultFactory = $resultFactory;
        $this->customerSession = $customerSession;
        $this->url = $url;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $orderId = (int) $this->request->getParam('id');

        if ($orderId === 0) {
            return $result->setData([
                static::SUCCESS_FIELD => false,
                'message' => __('Wrong Request')
            ]);
        }

        try {
            $order = $this->orderRepository->get($orderId);

            if ($order->getCustomerId() !== $this->customerSession->getCustomerId()) {
                throw new \Exception(__('Wrong Request'));
            }

            $payment = $order->getPayment();

            $paymentInformation = $payment->getAdditionalInformation();

            if (is_array($paymentInformation) &&
                array_key_exists(PayUConfigInterface::PAYU_REDIRECT_URI_CODE, $paymentInformation)) {
                $returnData = [
                    static::SUCCESS_FIELD => true,
                    PayUConfigInterface::REDIRECT_URI_FIELD => $paymentInformation[PayUConfigInterface::PAYU_REDIRECT_URI_CODE]
                ];
            } elseif (is_array($paymentInformation) &&
                array_key_exists(PayUConfigInterface::PAYU_SHOW_CVV_WIDGET, $paymentInformation)) {
                $this->customerSession->setCvvUrl(true);
                $returnData = [
                    static::SUCCESS_FIELD => true,
                    PayUConfigInterface::REDIRECT_URI_FIELD => $this->url->getUrl('checkout/onepage/continueCvv')
                ];
            } else {
                $returnData = [
                    static::SUCCESS_FIELD => true,
                    PayUConfigInterface::REDIRECT_URI_FIELD => $this->url->getUrl('checkout/onepage/success')
                ];
            }
        } catch (\Exception $exception) {
            $returnData = [
                static::SUCCESS_FIELD => false,
                'message' => $exception->getMessage()
            ];
        }
        return $result->setData($returnData);
    }
}
