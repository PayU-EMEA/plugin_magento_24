<?php

namespace PayU\PaymentGateway\Api;

/**
 * Interface PayUConfigInterface
 * @package PayU\PaymentGateway\Api
 */
interface PayUConfigInterface
{

    /**
     * Redirect url key for UI
     */
    public const COMMON_CONFIGURATION_METHOD_CODE = 'payu';

    /**
     * Redirect url key for UI
     */
    public const REDIRECT_URI_FIELD = 'redirectUri';

    /**
     * Method code key
     */
    public const PAYU_METHOD_CODE = 'payu_method';

    /**
     * Method type key
     */
    public const PAYU_METHOD_TYPE_CODE = 'payu_method_type';

    /**
     * Google Pay authorization token key
     */
    public const PAYU_AUTHORIZATION_CODE = 'payu_authorization_code';

    /**
     * Google Pay method mapping in PayU API
     */
    public const PAYU_GOOGLE_PAY_METHOD_VALUE = 'ap';

    /**
     * Redirect url key
     */
    public const PAYU_REDIRECT_URI_CODE = 'payu_redirect_uri';

    /**
     * Show CCV Widget key
     */
    public const PAYU_SHOW_CVV_WIDGET = 'payuShowCvvWidget';

    /**
     * Response status key
     */
    public const PAYU_RESPONSE_STATUS = 'payu_response_status';

    /**
     * Sandbox code
     */
    public const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * Secure code
     */
    public const ENVIRONMENT_SECURE = 'secure';

    /**
     * Client Credentials code
     */
    public const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';

    /**
     * Trusted Merchant code
     */
    public const GRANT_TYPE_TRUSTED_MERCHANT = 'trusted_merchant';

    /**
     * Terms url
     */
    public const PAYU_TERMS_URL = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_pl.pdf';

    /**
     * PayU logo static link
     */
    public const PAYU_BANK_TRANSFER_LOGO_SRC = 'PayU_PaymentGateway::images/payu-logo.svg';

    /**
     * PayU card logo static link
     */
    public const PAYU_CC_TRANSFER_LOGO_SRC = 'PayU_PaymentGateway::images/card-visa-mc.svg';

    /**
     * Pay by links code
     */
    public const PAYU_BANK_TRANSFER_KEY = 'PBL';

    /**
     * Pay by card code
     */
    public const PAYU_CC_TRANSFER_KEY = 'CARD_TOKEN';

    /**
     * Browser data
     */
    public const PAYU_BROWSER_PREFIX = 'payu_browser_';
    public const PAYU_BROWSER_SCREEN_WIDTH = 'screenWidth';
    public const PAYU_BROWSER_JAVA_ENABLED = 'javaEnabled';
    public const PAYU_BROWSER_TIMEZONE_OFFSET = 'timezoneOffset';
    public const PAYU_BROWSER_SCREEN_HEIGHT = 'screenHeight';
    public const PAYU_BROWSER_USER_AGENT = 'userAgent';
    public const PAYU_BROWSER_COLOR_DEPTH = 'colorDepth';
    public const PAYU_BROWSER_LANGUAGE = 'language';

    public const PAYU_BROWSER = [
        self::PAYU_BROWSER_SCREEN_WIDTH,
        self::PAYU_BROWSER_JAVA_ENABLED,
        self::PAYU_BROWSER_TIMEZONE_OFFSET,
        self::PAYU_BROWSER_SCREEN_HEIGHT,
        self::PAYU_BROWSER_USER_AGENT,
        self::PAYU_BROWSER_COLOR_DEPTH,
        self::PAYU_BROWSER_LANGUAGE,
    ];

    /**
     * Set Environment SECURE|SANDBOX
     */
    public function setEnvironment(string $environment): PayUConfigInterface;

    /**
     * Set merchant POS ID
     */
    public function setMerchantPosId(string $merchantPosId): PayUConfigInterface;

    /**
     * Set signature key
     */
    public function setSignatureKey(string $signatureKey): PayUConfigInterface;

    /**
     * Set OAuth client ID
     */
    public function setOauthClientId(string $oAuthClientId): PayUConfigInterface;

    /**
     * Set OAuth client secret
     */
    public function setOauthClientSecret(string $oAuthClientSecret): PayUConfigInterface;

    /**
     * Set OAuth grant type
     */
    public function setOauthGrantType(string $oAuthGrantType): PayUConfigInterface;

    /**
     * Set OAuth email
     */
    public function setOauthEmail(string $email): PayUConfigInterface;

    /**
     * Set customer external ID
     */
    public function setCustomerExtId(int $customerId): PayUConfigInterface;

    /**
     * Set default config
     * @throws \Exception
     */
    public function setDefaultConfig(string $code, ?int $storeId = null): PayUConfigInterface;

    /**
     * Set gateway config code
     */
    public function setGatewayConfigCode(string $code): PayUConfigInterface;

    /**
     * Set sender
     */
    public function setSender(string $sender): PayUConfigInterface;

    /**
     * Check if environment is sandbox
     */
    public function isSandboxEnv(?int $storeId): bool;

    /**
     * Check if credit card store per user is enabled
     */
    public function isStoreCardEnable(): bool;

    /**
     * Check if repayment is enabled
     */
    public function isRepaymentActive(string $code): bool;

    /**
     * Get payment methods order
     */
    public function getPaymentMethodsOrder(): array;

    /**
     * Check if cancel order on PayU Payment Wall is enabled
     */
    public function canCancelOrderOnPaymentWall(string $code): bool;
}
