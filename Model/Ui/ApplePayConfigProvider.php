<?php

namespace PayU\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\StoreManagerInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

class ApplePayConfigProvider implements ConfigProviderInterface
{
    private AssetRepository $assetRepository;

    private GatewayConfig $gatewayConfig;

    private int $storeId;

    private ResolverInterface $resolver;

    public function __construct(
        AssetRepository $assetRepository,
        GatewayConfig $gatewayConfig,
        StoreManagerInterface $storeManager,
        ResolverInterface $resolver
    ) {
        $this->assetRepository = $assetRepository;
        $this->gatewayConfig = $gatewayConfig;
        $this->storeId = $storeManager->getStore()->getId();
        $this->resolver = $resolver;
    }

    public function getConfig(): array
    {
        $isSandbox = $this->isSandboxEnv($this->storeId);
        $domainName = $this->resolveDomainName();
        $displayName = $this->resolveDisplayName();

        return [
            'payment' => [
                'payuApplePay' => [
                    'isActive' => $this->isApplePayActive($domainName, $displayName),
                    'logoSrc' => $this->assetRepository->getUrl('PayU_PaymentGateway::images/payu_apple_pay.svg'),
                    'termsUrl' => PayUConfigInterface::PAYU_TERMS_URL,
                    'language' => $this->getLanguage(),
                    'environment' => $isSandbox ? 'TEST' : 'PRODUCTION',
                    'domainName' => $domainName,
                    'displayName' => $displayName,
                ],
            ],
        ];
    }

    private function isApplePayActive(string $domainName, string $displayName): bool
    {
        $isActiveInConfig = (bool) $this->getApplePayConfigValue('active');

        return $isActiveInConfig && !empty($domainName) && !empty($displayName);
    }

    private function resolveDomainName(): string
    {
        $domainName = $this->getApplePayConfigValue('domain_name');

        return is_string($domainName) ? trim($domainName) : '';
    }

    private function resolveDisplayName(): string
    {
        $displayName = $this->getApplePayConfigValue('store_display_name');

        return is_string($displayName) ? trim($displayName) : '';
    }

    private function getApplePayConfigValue(string $key)
    {
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_APPLE_PAY);

        return $this->gatewayConfig->getValue($key, $this->storeId);
    }

    private function getLanguage(): string
    {
        return current(explode('_', $this->resolver->getLocale()));
    }

    public function isSandboxEnv(?int $storeId): bool
    {
        $this->gatewayConfig->setMethodCode('payu');
        $flag = $this->gatewayConfig->getValue('environment', $storeId);

        return $flag === '1';
    }
}

