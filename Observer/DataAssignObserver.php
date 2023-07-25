<?php

namespace PayU\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;

/**
 * Class DataAssignObserver
 * @package PayU\PaymentGateway\Observer
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var array
     */
    protected $additionalList = [
        PayUConfigInterface::PAYU_METHOD_CODE,
        PayUConfigInterface::PAYU_METHOD_TYPE_CODE,
        PayUConfigInterface::PAYU_BROWSER_PREFIX . PayUConfigInterface::PAYU_BROWSER_SCREEN_WIDTH,
        PayUConfigInterface::PAYU_BROWSER_PREFIX . PayUConfigInterface::PAYU_BROWSER_JAVA_ENABLED,
        PayUConfigInterface::PAYU_BROWSER_PREFIX . PayUConfigInterface::PAYU_BROWSER_TIMEZONE_OFFSET,
        PayUConfigInterface::PAYU_BROWSER_PREFIX . PayUConfigInterface::PAYU_BROWSER_SCREEN_HEIGHT,
        PayUConfigInterface::PAYU_BROWSER_PREFIX . PayUConfigInterface::PAYU_BROWSER_USER_AGENT,
        PayUConfigInterface::PAYU_BROWSER_PREFIX . PayUConfigInterface::PAYU_BROWSER_COLOR_DEPTH,
        PayUConfigInterface::PAYU_BROWSER_PREFIX . PayUConfigInterface::PAYU_BROWSER_LANGUAGE
    ];

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }
        $paymentInfo = $this->readPaymentModelArgument($observer);
        foreach ($this->additionalList as $additionalKey) {
            if (isset($additionalData[$additionalKey])) {
                $paymentInfo->setAdditionalInformation($additionalKey, $additionalData[$additionalKey]);
            }
        }
    }
}
