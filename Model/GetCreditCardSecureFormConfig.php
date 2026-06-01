<?php

namespace PayU\PaymentGateway\Model;

use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayUGetCreditCardSecureFormConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class GetCreditCardSecureFormConfig
 * @package PayU\PaymentGateway\Model
 */
class GetCreditCardSecureFormConfig implements PayUGetCreditCardSecureFormConfigInterface
{
    /**
     * @var \OpenPayU_Configuration
     */
    private $openPayUConfig;

    /**
     * @var PayUConfigInterface
     */
    private $payUConfig;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * GetCreditCardSecureFormConfig constructor.
     *
     * @param \OpenPayU_Configuration $openPayUConfig
     * @param PayUConfigInterface $payUConfig
     * @param CustomerSession $customerSession
     */
    public function __construct(
        \OpenPayU_Configuration $openPayUConfig,
        PayUConfigInterface $payUConfig,
        CustomerSession $customerSession
    ) {
        $this->openPayUConfig = $openPayUConfig;
        $this->payUConfig = $payUConfig;
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->payUConfig->setDefaultConfig(PayUSupportedMethods::CODE_CARD);
        $config = $this->openPayUConfig;

        $storeCard = $this->payUConfig->isStoreCardEnable() && $this->customerSession->isLoggedIn();

        return [
            static::CONFIG_ENV => $config::getEnvironment(),
            static::CONFIG_POS_ID => $config::getMerchantPosId(),
            static::CONFIG_STORE_CARD => $storeCard
        ];
    }
}
