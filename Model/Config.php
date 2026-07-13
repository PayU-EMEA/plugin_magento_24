<?php

namespace PayU\PaymentGateway\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use PayU\PaymentGateway\Model\Logger\Logger;

/**
 * Class Config
 * @package PayU\PaymentGateway\Model
 */
class Config implements PayUConfigInterface
{
    /**
     * Current Plugin Version
     */
    private const PLUGIN_VERSION = '2.0.8';

    private \OpenPayU_Configuration $openPayUConfig;

    private GatewayConfig $gatewayConfig;

    private int $storeId;

    private ProductMetadataInterface $metadata;

    private Logger $logger;


    /**
     * Config constructor.
     *
     * @throws NoSuchEntityException
     */
    public function __construct(
        \OpenPayU_Configuration $openPayUConfig,
        GatewayConfig $gatewayConfig,
        StoreManagerInterface $storeManager,
        Logger $logger,
        ProductMetadataInterface $metadata
    ) {
        $this->openPayUConfig = $openPayUConfig;
        $this->gatewayConfig = $gatewayConfig;
        $this->storeId = $storeManager->getStore()->getId();
        $this->logger = $logger;
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

        // common config
        $isSandboxEnvironment = $this->isSandboxEnv($storeId);
        $this->setGatewayConfigCode(PayUConfigInterface::COMMON_CONFIGURATION_METHOD_CODE);
        $envPrefix = $isSandboxEnvironment ? 'sandbox_' : '';
        $posId = $this->gatewayConfig->getValue($envPrefix . 'pos_id', $storeId);
        $signatureKey = $this->gatewayConfig->getValue($envPrefix . 'second_key', $storeId);
        $clientId = $this->gatewayConfig->getValue($envPrefix . 'client_id', $storeId);
        $clientSecret = $this->gatewayConfig->getValue($envPrefix . 'client_secret', $storeId);

        if (empty($posId) || empty($signatureKey) || empty($clientId) || empty($clientSecret)) {
            throw new \Exception('Empty PayU Configuration');
        }

        $this->setGatewayConfigCode($code);

        try {
            $environment = $isSandboxEnvironment ? PayUConfigInterface::ENVIRONMENT_SANDBOX : PayUConfigInterface::ENVIRONMENT_SECURE;
            $this
                ->setEnvironment($environment)
                ->setMerchantPosId($posId)
                ->setSignatureKey($signatureKey)
                ->setOauthClientId($clientId)
                ->setOauthClientSecret($clientSecret)
                ->setOauthGrantType(PayUConfigInterface::GRANT_TYPE_CLIENT_CREDENTIALS)
                ->setSender('Magento 2 ver ' . $this->metadata->getVersion() . '/Plugin ver ' . static::PLUGIN_VERSION);
        } catch (\OpenPayU_Exception_Configuration $exception) {
            $this->logger->critical('Problem with set PayU Configuration', [$exception->getMessage()]);
            throw new \Exception('Problem with PayU Configuration');
        }

        return $this;
    }

    public function isSandboxEnv(?int $storeId): bool {
        $this->gatewayConfig->setMethodCode(PayUConfigInterface::COMMON_CONFIGURATION_METHOD_CODE);
        $flag = $this->gatewayConfig->getValue('environment', $storeId);

        if ($flag === null) {
            $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_GATEWAY);
            $flag = $this->gatewayConfig->getValue('environment', $storeId);
        }
        if ($flag === null) {
            $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_CARD);
            $flag = $this->gatewayConfig->getValue('environment', $storeId);
        }

        return $flag === '1';
    }

    /**
     * {@inheritdoc}
     */
    public function isStoreCardEnable(): bool
    {
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_CARD);
        return (bool)$this->gatewayConfig->getValue('store_card', $this->storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodsOrder(): array
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
    public function isRepaymentActive($code): bool
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
    public function setEnvironment(string $environment): PayUConfigInterface
    {
        $config = $this->openPayUConfig;
        $config::setEnvironment($environment);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMerchantPosId(string $merchantPosId): PayUConfigInterface
    {
        $config = $this->openPayUConfig;
        $config::setMerchantPosId($merchantPosId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSignatureKey(string $signatureKey): PayUConfigInterface
    {
        $config = $this->openPayUConfig;
        $config::setSignatureKey($signatureKey);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOauthClientId(string $oAuthClientId): PayUConfigInterface
    {
        $config = $this->openPayUConfig;
        $config::setOauthClientId($oAuthClientId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOauthClientSecret(string $oAuthClientSecret): PayUConfigInterface
    {
        $config = $this->openPayUConfig;
        $config::setOauthClientSecret($oAuthClientSecret);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOauthGrantType(string $oAuthGrantType): PayUConfigInterface
    {
        $config = $this->openPayUConfig;
        $config::setOauthGrantType($oAuthGrantType);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOauthEmail(string $email): PayUConfigInterface
    {
        $config = $this->openPayUConfig;
        $config::setOauthEmail($email);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerExtId(int $customerId): PayUConfigInterface
    {
        $config = $this->openPayUConfig;
        $config::setOauthExtCustomerId($customerId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSender($sender): PayUConfigInterface
    {
        $config = $this->openPayUConfig;
        $config::setSender($sender);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setGatewayConfigCode($code): PayUConfigInterface
    {
        $this->gatewayConfig->setMethodCode($code);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function canCancelOrderOnPaymentWall(string $code): bool
    {
        $this->setGatewayConfigCode($code);

        return (bool)$this->gatewayConfig->getValue('can_cancel_order_on_payment_wall', $this->storeId);
    }
}
