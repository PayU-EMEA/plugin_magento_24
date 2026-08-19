<?php

namespace PayU\PaymentGateway\Block\Order\Repay;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PayU\PaymentGateway\Api\GetAvailableLocaleInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayUGetCreditCardSecureFormConfigInterface;
use PayU\PaymentGateway\Api\PayUGetPayMethodsInterface;
use PayU\PaymentGateway\Api\PayUGetUserPayMethodsInterface;
use PayU\PaymentGateway\Model\PayUSupportedMethods;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;

/**
 * Class PaymentMethods
 * @package Payu\PaymentGateway\Block\Order\Repay
 */
class PaymentMethods extends Template
{
    private const CODE = 'code';
    private const LOGO_SRC = 'logoSrc';
    private const ORDER_ID = 'orderId';
    private const LANGUAGE = 'language';
    private const TERMS_URL = 'termsUrl';
    private const TRANSFER_KEY = 'transferKey';
    private const REPAY_URL = 'repayUrl';
    private const STORED_CARDS = 'storedCards';
    private const SECURE_FORM = 'secureForm';
    private const ACTIVE = 'active';
    private const METHODS = 'methods';
    private const AMOUNT = 'amount';
    private const CURRENCY_CODE = 'currencyCode';
    private const ENVIRONMENT = 'environment';
    private const GATEWAY_MERCHANT_ID = 'gatewayMerchantId';
    private const GOOGLE_MERCHANT_NAME = 'googleMerchantName';
    private const GOOGLE_MERCHANT_ID = 'googleMerchantId';

    private RequestInterface $request;
    private PayUGetPayMethodsInterface $payMethods;
    private PayUGetCreditCardSecureFormConfigInterface $secureFormConfig;
    private OrderRepositoryInterface $orderRepository;
    private GetAvailableLocaleInterface $availableLocale;
    private PayUGetUserPayMethodsInterface $userPayMethods;
    private ?OrderInterface $order = null;
    private GatewayConfig $gatewayConfig;

    public function __construct(
        Context $context,
        RequestInterface $request,
        PayUGetPayMethodsInterface $payMethods,
        PayUGetCreditCardSecureFormConfigInterface $secureFormConfig,
        OrderRepositoryInterface $orderRepository,
        GetAvailableLocaleInterface $availableLocale,
        PayUGetUserPayMethodsInterface $userPayMethods,
        GatewayConfig $gatewayConfig,
        array $data = []
    ) {
        $this->payMethods = $payMethods;
        $this->request = $request;
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
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_GATEWAY);
        if (!(bool)$this->gatewayConfig->getValue(self::ACTIVE, $storeId)) {
            return "";
        }
        $paymethods = $this->payMethods->getAllPayMethodsForPbl(false, $this->getOrder()->getGrandTotal());

        if (empty($paymethods)) {
            return "";
        }

        return json_encode(
            [
                self::CODE => PayUSupportedMethods::CODE_GATEWAY,
                self::LOGO_SRC => $this->getViewFileUrl(PayUConfigInterface::PAYU_BANK_TRANSFER_LOGO_SRC),
                self::ORDER_ID => $this->getOrder()->getEntityId(),
                self::LANGUAGE => $this->availableLocale->execute(),
                self::TERMS_URL => PayUConfigInterface::PAYU_TERMS_URL,
                self::TRANSFER_KEY => PayUConfigInterface::PAYU_BANK_TRANSFER_KEY,
                self::REPAY_URL => $this->getRepaymentUrl(),
                self::METHODS => $paymethods,
            ],
        );
    }

    /**
     * Get config for PayU Card Payment Gateway
     */
    public function getCardPaymentGatewayConfig(): string
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_CARD);
        if (!(bool)$this->gatewayConfig->getValue(self::ACTIVE, $storeId)) {
            return "";
        }

        $allMethods = $this->payMethods->getAllAvailablePayMethods($this->getOrder()->getGrandTotal());
        $hasCardMethod = (bool) array_filter(
            $allMethods,
            static function ($method): bool {
                return $method->value === 'c';
            }
        );
        if (!$hasCardMethod) {
             return "";
        }

        $userPayMethods = $this->getUserStoredCards();

        return json_encode(
            [
                self::CODE => PayUSupportedMethods::CODE_CARD,
                self::LOGO_SRC => $this->getViewFileUrl(PayUConfigInterface::PAYU_CC_TRANSFER_LOGO_SRC),
                self::ORDER_ID => $this->getOrder()->getEntityId(),
                self::LANGUAGE => $this->availableLocale->execute(),
                self::TERMS_URL => PayUConfigInterface::PAYU_TERMS_URL,
                self::TRANSFER_KEY => PayUConfigInterface::PAYU_CC_TRANSFER_KEY,
                self::REPAY_URL => $this->getRepaymentUrl(),
                self::STORED_CARDS => array_key_exists(PayUGetUserPayMethodsInterface::CARD_TOKENS, $userPayMethods) && $userPayMethods[PayUGetUserPayMethodsInterface::CARD_TOKENS] ? $userPayMethods[PayUGetUserPayMethodsInterface::CARD_TOKENS] : [],
                self::SECURE_FORM => $this->secureFormConfig->execute(),
            ],
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
            $this->getOrder()->getCustomerId(),
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

    private function getRepaymentUrl(): string
    {
        $requestHash = $this->request->getParam('hash');

        return 'sales/order/repay' . ($requestHash ? '/hash/' . $requestHash : '');
    }

    public function getGooglePayPaymentGatewayConfig(): string
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_GOOGLE_PAY);
        if (!(bool)$this->gatewayConfig->getValue(self::ACTIVE, $storeId)) {
            return "";
        }

        $allMethods = $this->payMethods->getAllAvailablePayMethods($this->getOrder()->getGrandTotal());
        $hasGooglePayMethod = (bool) array_filter(
            $allMethods,
            static function ($method): bool {
                return $method->value === 'ap';
            }
        );
        if (!$hasGooglePayMethod) {
            return "";
        }

        return json_encode(
            [
                self::CODE => PayUSupportedMethods::CODE_GOOGLE_PAY,
                self::LOGO_SRC => $this->getViewFileUrl(PayUConfigInterface::PAYU_GOOGLE_PAY_TRANSFER_LOGO_SRC),
                self::ORDER_ID => $this->getOrder()->getEntityId(),
                self::LANGUAGE => $this->availableLocale->execute(),
                self::TERMS_URL => PayUConfigInterface::PAYU_TERMS_URL,
                self::REPAY_URL => $this->getRepaymentUrl(),
                self::AMOUNT => (float)$this->getOrder()->getGrandTotal(),
                self::CURRENCY_CODE => (string)$this->getOrder()->getOrderCurrencyCode(),
                self::ENVIRONMENT => $this->getGooglePayEnv(),
                self::GATEWAY_MERCHANT_ID => $this->getGooglePayGatewayMerchantId(),
                self::GOOGLE_MERCHANT_ID => $this->getGooglePayMerchantId(),
                self::GOOGLE_MERCHANT_NAME => $this->getGooglePayMerchantName(),
            ],
        );
    }

    public function getGooglePayConfig(): string
    {
        return $this->getGooglePayPaymentGatewayConfig();
    }

    private function getGooglePayEnv(): string
    {
        return $this->isSandboxEnv() ? 'TEST' : 'PRODUCTION';
    }

    private function getGooglePayGatewayMerchantId(): string
    {
        $configKey = $this->isSandboxEnv() ? 'sandbox_pos_id' : 'pos_id';

        $this->gatewayConfig->setMethodCode('payu');
        $gatewayMerchantId = $this->gatewayConfig->getValue($configKey, $this->_storeManager->getStore()->getId());

        return is_string($gatewayMerchantId) ? trim($gatewayMerchantId) : '';
    }

    private function getGooglePayMerchantId(): string
    {
        if ($this->isSandboxEnv()) {
            return '0';
        }

        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_GOOGLE_PAY);
        $googleMerchantId = $this->gatewayConfig->getValue('google_merchant_id', $this->_storeManager->getStore()->getId());

        return is_string($googleMerchantId) ? trim($googleMerchantId) : '';
    }

    private function getGooglePayMerchantName(): string
    {
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_GOOGLE_PAY);
        $googleMerchantName = $this->gatewayConfig->getValue('google_merchant_name', $this->_storeManager->getStore()->getId());

        return is_string($googleMerchantName) ? trim($googleMerchantName) : '';
    }

    private function isSandboxEnv(): bool
    {
        $storeId = $this->_storeManager->getStore()->getId();

        $this->gatewayConfig->setMethodCode('payu');
        $flag = $this->gatewayConfig->getValue('environment', $storeId);

        return $flag === '1';
    }
}
