<?php

namespace PayU\PaymentGateway\Controller\Data;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use PayU\PaymentGateway\Api\PayUConfigInterface;


class GetPostPlaceOrderData implements HttpGetActionInterface
{
    const SUCCESS_FIELD = 'success';
    private Session $checkoutSession;
    private CustomerSession $customerSession;
    private ResultFactory $resultFactory;

    public function __construct(
        ResultFactory   $resultFactory,
        Session         $checkoutSession,
        CustomerSession $customerSession
    )
    {
        $this->resultFactory = $resultFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            /** @var $payment \Magento\Sales\Model\Order\Payment */
            $payment = $this->checkoutSession->getLastRealOrder()->getPayment();
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
                    PayUConfigInterface::REDIRECT_URI_FIELD => $this->_url->getUrl('checkout/onepage/continueCvv')
                ];
            } else {
                $returnData = [
                    static::SUCCESS_FIELD => true,
                    PayUConfigInterface::REDIRECT_URI_FIELD => $this->_url->getUrl('checkout/onepage/success')
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
