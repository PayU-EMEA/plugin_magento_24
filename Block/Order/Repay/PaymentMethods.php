<?php

namespace PayU\PaymentGateway\Block\Order\Repay;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PayU\PaymentGateway\Api\GetAvailableLocaleInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayUGetCreditCardSecureFormConfigInterface;
use PayU\PaymentGateway\Api\PayUGetPayMethodsInterface;
use PayU\PaymentGateway\Api\PayUGetUserPayMethodsInterface;
use PayU\PaymentGateway\Model\Ui\CardConfigProvider;
use PayU\PaymentGateway\Model\Ui\ConfigProvider;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;

/**
 * Class PaymentMethods
 * @package Payu\PaymentGateway\Block\Order\Repay
 */
class PaymentMethods extends Template
{
    /**
     * Code
     */
    const CODE = 'code';

    /**
     * Logo url
     */
    const LOGO_SRC = 'logoSrc';

    /**
     * Order ID
     */
    const ORDER_ID = 'orderId';

    /**
     * Locale
     */
    const LANGUAGE = 'language';

    /**
     * Terms URL
     */
    const TERMS_URL = 'termsUrl';

    /**
     * Transfer Key
     */
    const TRANSFER_KEY = 'transferKey';

    /**
     * Repay URL
     */
    const REPAY_URL = 'repayUrl';

    /**
     * Stored Cards List
     */
    const STORED_CARDS = 'storedCards';

    /**
     * Secure Form Config
     */
    const SECURE_FORM = 'secureForm';

    /**
     * Repay address URL
     */
    const REPAY_URI = 'sales/order/repay';

    /**
     * Active gateway code
     */
    const ACTIVE = 'active';

    private PayUGetPayMethodsInterface $payMethods;
    private OrderRepositoryInterface $orderRepository;
    private GetAvailableLocaleInterface $availableLocale;
    private PayUGetUserPayMethodsInterface $userPayMethods;
    private ?OrderInterface $order = null;
    private GatewayConfig $gatewayConfig;

    /**
     * @param Context $context
     * @param PayUGetPayMethodsInterface $payMethods
     * @param PayUGetCreditCardSecureFormConfigInterface $secureFormConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param GetAvailableLocaleInterface $availableLocale
     * @param PayUGetUserPayMethodsInterface $userPayMethods
     * @param GatewayConfig $gatewayConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        PayUGetPayMethodsInterface $payMethods,
        PayUGetCreditCardSecureFormConfigInterface $secureFormConfig,
        OrderRepositoryInterface $orderRepository,
        GetAvailableLocaleInterface $availableLocale,
        PayUGetUserPayMethodsInterface $userPayMethods,
        GatewayConfig $gatewayConfig,
        array $data = []
    ) {
        $this->payMethods = $payMethods;
        $this->secureFormConfig = $secureFormConfig;
        $this->orderRepository = $orderRepository;
        $this->availableLocale = $availableLocale;
        $this->userPayMethods = $userPayMethods;
        $this->gatewayConfig = $gatewayConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get config for PayU Payment Gateway
     */
    public function getPaymentGatewayConfig(): string
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $this->gatewayConfig->setMethodCode(ConfigProvider::CODE);
        if (!(bool)$this->gatewayConfig->getValue(static::ACTIVE, $storeId)) {
            return "";
        }

        return json_encode(
            [
                static::CODE => ConfigProvider::CODE,
                static::LOGO_SRC => $this->getViewFileUrl(PayUConfigInterface::PAYU_BANK_TRANSFER_LOGO_SRC),
                static::ORDER_ID => $this->getOrder()->getEntityId(),
                static::LANGUAGE => $this->availableLocale->execute(),
                static::TERMS_URL => PayUConfigInterface::PAYU_TERMS_URL,
                static::TRANSFER_KEY => PayUConfigInterface::PAYU_BANK_TRANSFER_KEY,
                static::REPAY_URL => static::REPAY_URI,
                'methods' => $this->payMethods->execute(ConfigProvider::CODE)
            ]
        );
    }

    /**
     * Get config for PayU Card Payment Gateway
     */
    public function getCardPaymentGatewayConfig(): string
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $this->gatewayConfig->setMethodCode(CardConfigProvider::CODE);
        if (!(bool)$this->gatewayConfig->getValue(static::ACTIVE, $storeId)) {
            return "";
        }

        $userPayMethods = $this->getUserStoredCards();

        return json_encode(
            [
                static::CODE => CardConfigProvider::CODE,
                static::LOGO_SRC => $this->getViewFileUrl(PayUConfigInterface::PAYU_CC_TRANSFER_LOGO_SRC),
                static::ORDER_ID => $this->getOrder()->getEntityId(),
                static::LANGUAGE => $this->availableLocale->execute(),
                static::TERMS_URL => PayUConfigInterface::PAYU_TERMS_URL,
                static::TRANSFER_KEY => PayUConfigInterface::PAYU_CC_TRANSFER_KEY,
                static::REPAY_URL => static::REPAY_URI,
                static::STORED_CARDS => array_key_exists(PayUGetUserPayMethodsInterface::CARD_TOKENS, $userPayMethods) && $userPayMethods[PayUGetUserPayMethodsInterface::CARD_TOKENS] ? $userPayMethods[PayUGetUserPayMethodsInterface::CARD_TOKENS] : [],
                static::SECURE_FORM => $this->secureFormConfig->execute()
            ]
        );
    }

    public function getCardEnv(): string
    {
        return $this->secureFormConfig->execute()[PayUGetCreditCardSecureFormConfigInterface::CONFIG_ENV];
    }

    private function getUserStoredCards(): array
    {
        return $this->userPayMethods->execute(
            $this->getOrder()->getCustomerEmail(),
            $this->getOrder()->getCustomerId()
        );
    }

    private function getOrder(): OrderInterface
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        if ($this->order === null) {
            $this->order = $this->orderRepository->get($orderId);
        }

        return $this->order;
    }
}
