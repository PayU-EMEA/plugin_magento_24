<?php

namespace PayU\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

class GooglePayConfigProvider implements ConfigProviderInterface
{

    private AssetRepository $assetRepository;

    private GatewayConfig $gatewayConfig;

    private int $storeId;

    private ResolverInterface $resolver;

    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        AssetRepository $assetRepository,
        GatewayConfig $gatewayConfig,
        StoreManagerInterface $storeManager,
        ResolverInterface $resolver,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->assetRepository = $assetRepository;
        $this->gatewayConfig = $gatewayConfig;
        $this->storeId = $storeManager->getStore()->getId();
        $this->resolver = $resolver;
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig(): array
    {
        $isSandbox = $this->isSandboxEnvironment();
        $gatewayMerchantId = $this->resolveGooglePayGatewayMerchantId($isSandbox);
        $googleMerchantName = $this->resolveGooglePayMerchantName();
        $merchantId = $this->resolveGooglePayMerchantId($isSandbox);

        return [
            'payment' => [
                'payuGooglePay' => [
                    'isActive' => $this->isGooglePayActive($isSandbox, $gatewayMerchantId, $merchantId),
                    'title' => (string) __('Google Pay'),
                    'logoSrc' => $this->assetRepository->getUrl('PayU_PaymentGateway::images/payu_google_pay_logo.svg'),
                    'termsUrl' => PayUConfigInterface::PAYU_TERMS_URL,
                    'language' => $this->getLanguage(),
                    'environment' => $isSandbox ? 'TEST' : 'PRODUCTION',
                    'gatewayMerchantId' => $gatewayMerchantId,
                    'merchantId' => $merchantId,
                    'googleMerchantName' => $googleMerchantName,
                    'allowedCardNetworks' => ['VISA', 'MASTERCARD'],
                    'allowedAuthMethods' => ['PAN_ONLY', 'CRYPTOGRAM_3DS']
                ]
            ]
        ];
    }

    private function isGooglePayActive(bool $isSandbox, string $gatewayMerchantId, string $merchantId): bool
    {
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_GOOGLE_PAY);
        $isActiveInConfig = (bool) $this->gatewayConfig->getValue('active', $this->storeId);

        if (!$isActiveInConfig) {
            return false;
        }

        return $isSandbox
            ? $this->validateSandboxConfiguration($gatewayMerchantId)
            : $this->validateProductionConfiguration($gatewayMerchantId, $merchantId);
    }

    private function validateSandboxConfiguration(string $gatewayMerchantId): bool
    {
        return !empty($gatewayMerchantId);
    }

    private function validateProductionConfiguration(string $gatewayMerchantId, string $merchantId): bool
    {
        return !empty($gatewayMerchantId) && !empty($merchantId);
    }

    private function getLanguage(): string
    {
        return current(explode('_', $this->resolver->getLocale()));
    }


    private function resolveGooglePayGatewayMerchantId(bool $isSandbox): string
    {
        $configPath = $isSandbox ? 'payment/payu/sandbox_pos_id' : 'payment/payu/pos_id';
        $gatewayMerchantId = $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $this->storeId);

        return is_string($gatewayMerchantId) ? trim($gatewayMerchantId) : '';
    }

    private function resolveGooglePayMerchantId(bool $isSandbox): string
    {
        if ($isSandbox) {
            return '0';
        }

        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_GOOGLE_PAY);
        $merchantId = $this->gatewayConfig->getValue('merchant_id', $this->storeId);

        return is_string($merchantId) ? trim($merchantId) : '';
    }

    private function resolveGooglePayMerchantName(): string
    {
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_GOOGLE_PAY);
        $googleMerchantName = $this->gatewayConfig->getValue('google_merchant_name', $this->storeId);

        return is_string($googleMerchantName) ? trim($googleMerchantName) : '';
    }

    private function isSandboxEnvironment(): bool
    {
        $environment = $this->scopeConfig->getValue('payment/payu/environment', ScopeInterface::SCOPE_STORE, $this->storeId);

        return $environment === '1';
    }
}


