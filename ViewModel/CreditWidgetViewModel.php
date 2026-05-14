<?php

namespace PayU\PaymentGateway\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\StoreManagerInterface;

class CreditWidgetViewModel implements ArgumentInterface
{
    private GatewayConfig $gatewayConfig;
    private int $storeId;
    private string $currencyCode;
    private string $languageCode;

    public function __construct(
        GatewayConfig $gatewayConfig,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $store = $storeManager->getStore();
        $this->storeId = $store->getId();
        $this->currencyCode = (string)$store->getCurrentCurrencyCode();
        $this->languageCode = $this->normalizeLanguageCode($localeResolver->getLocale());
    }

    public function getPosId(): string {
        $this->gatewayConfig->setMethodCode('payu');
        return $this->gatewayConfig->getValue('pos_id', $this->storeId) ?? '';
    }

    public function getKey(): string {
        $this->gatewayConfig->setMethodCode('payu');
        $key = $this->gatewayConfig->getValue('client_secret', $this->storeId) ?? '';
        return substr($key, 0, 2);
    }

    public function getExcludedPaytypes(): array
    {
        $this->gatewayConfig->setMethodCode('payu_credit_widget');
        $excluded = $this->gatewayConfig->getValue('excluded_paytypes', $this->storeId);
        return empty($excluded) ? []: explode(',', str_replace(' ', '', $excluded));
    }

    /**
     * Check if PayU credit widget is enabled for the given context
     *
     * @param string $configKey The configuration key to check (e.g., 'enable_for_product', 'enable_for_catalog')
     * @return bool
     */
    public function isWidgetEnabled(string $configKey): bool
    {
        if (empty($this->getPosId()) || empty($this->getKey())) {
            return false;
        }

        $this->gatewayConfig->setMethodCode('payu_credit_widget');
        return (bool)$this->gatewayConfig->getValue($configKey, $this->storeId);
    }

    /**
     * Return the script URL
     *
     * @return string
     */
    public function getScriptUrl(): string
    {
        return 'https://static.payu.com/res/v2/widget-mini-installments.js';
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    private function normalizeLanguageCode(string $locale): string
    {
        return strtolower(substr($locale, 0, 2));
    }
}
