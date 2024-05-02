<?php

namespace PayU\PaymentGateway\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use PayU\PaymentGateway\Api\PayUCacheConfigInterface;
use PayU\PaymentGateway\Model\Ui\CardConfigProvider;

/**
 * Class Config
 * @package PayU\PaymentGateway\Model
 */
class Config implements PayUConfigInterface
{
    /**
     * Current Plugin Version
     */
    const PLUGIN_VERSION = '2.0.2';

    /**
     * @var \OpenPayU_Configuration
     */
    private $openPayUConfig;

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;

    /**
     * @var PayUCacheConfigInterface
     */
    private $cacheConfig;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ProductMetadataInterface
     */
    private $metadata;

    /**
     * @var array
     */
    private $environmentTypes = [PayUConfigInterface::ENVIRONMENT_SECURE, PayUConfigInterface::ENVIRONMENT_SANBOX];

    /**
     * Config constructor.
     *
     * @param \OpenPayU_Configuration $openPayUConfig
     * @param GatewayConfig $gatewayConfig
     * @param PayUCacheConfigInterface $cacheConfig
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @param ProductMetadataInterface $metadata
     */
    public function __construct(
        \OpenPayU_Configuration $openPayUConfig,
        GatewayConfig $gatewayConfig,
        PayUCacheConfigInterface $cacheConfig,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        ProductMetadataInterface $metadata
    ) {
        $this->openPayUConfig = $openPayUConfig;
        $this->gatewayConfig = $gatewayConfig;
        $this->cacheConfig = $cacheConfig;
        $this->storeId = $storeManager->getStore()->getId();
        $this->encryptor = $encryptor;
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultConfig($code, $storeId = null): PayUConfigInterface
    {
        if ($storeId !== null) {
            $this->storeId = $storeId;
        }
        $this->setGatewayConfigCode($code);
        if (!$this->isActive()) {
            $this->setMerchantPosId('');

            return $this;
        }

        // gateway config
        $isSandboxEnvironment = $this->gatewayConfig->getValue('environment', $storeId) === '1';

        // common config
        $envPrefix = $isSandboxEnvironment ? 'sandbox_' : '';
        $this->setGatewayConfigCode('payu');
        $this->gatewayConfig->setMethodCode('payu');
        $posId = $this->gatewayConfig->getValue($envPrefix . 'pos_id', $storeId);
        $signatureKey = $this->gatewayConfig->getValue($envPrefix . 'second_key', $storeId);
        $clientId = $this->gatewayConfig->getValue($envPrefix . 'client_id', $storeId);
        $clientSecret = $this->gatewayConfig->getValue($envPrefix . 'client_secret', $storeId);
        $this->setGatewayConfigCode($code);

        try {
            $environment = $isSandboxEnvironment ? PayUConfigInterface::ENVIRONMENT_SANBOX : PayUConfigInterface::ENVIRONMENT_SECURE;
            $this->setEnvironment($environment);
            $this->setMerchantPosId($posId);
            $this->setSignatureKey($signatureKey);
            $this->setOauthClientId($clientId);
            $this->setOauthClientSecret($clientSecret);
            $this->setOauthGrantType(PayUConfigInterface::GRANT_TYPE_CLIENT_CREDENTIALS);
            $this->setSender('Magento 2 ver ' . $this->metadata->getVersion() . '/Plugin ver ' . static::PLUGIN_VERSION);
        } catch (\OpenPayU_Exception_Configuration $exception) {
            return $this;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isStoreCardEnable()
    {
        return (bool)$this->gatewayConfig->getValue('store_card', $this->storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodsOrder()
    {
        return explode(
            ',',
            str_replace(
                ' ',
                '',
                $this->gatewayConfig->getValue('payment_methods_order', $this->storeId) ?? ''
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isRepaymentActive($code)
    {
        $this->setGatewayConfigCode($code);

        return (bool)$this->gatewayConfig->getValue('repayment', $this->storeId);
    }

    /**
     * Check if selected pay method is enabled
     */
    private function isActive(): bool
    {
        return (bool)$this->gatewayConfig->getValue('active', $this->storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function setEnvironment($environment)
    {
        $config = $this->openPayUConfig;
        $config::setEnvironment($environment);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMerchantPosId($merchantPosId)
    {
        $config = $this->openPayUConfig;
        $config::setMerchantPosId($merchantPosId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSignatureKey($signatureKey)
    {
        $config = $this->openPayUConfig;
        $config::setSignatureKey($signatureKey);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOauthClientId($oAuthClientId)
    {
        $config = $this->openPayUConfig;
        $config::setOauthClientId($oAuthClientId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOauthClientSecret($oAuthClientSecret)
    {
        $config = $this->openPayUConfig;
        $config::setOauthClientSecret($oAuthClientSecret);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOauthGrantType($oAuthGrantType)
    {
        $config = $this->openPayUConfig;
        $config::setOauthGrantType($oAuthGrantType);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOauthEmail($email)
    {
        $config = $this->openPayUConfig;
        $config::setOauthEmail($email);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerExtId($customerId)
    {
        $config = $this->openPayUConfig;
        $config::setOauthExtCustomerId($customerId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSender($sender)
    {
        $config = $this->openPayUConfig;
        $config::setSender($sender);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setGatewayConfigCode($code)
    {
        $this->gatewayConfig->setMethodCode($code);

        return $this;
    }

}
