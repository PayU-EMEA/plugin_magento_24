<?php

namespace PayU\PaymentGateway\Model;

use PayU\PaymentGateway\Api\PayUGetPayMethodsInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\GetAvailableLocaleInterface;
use PayU\PaymentGateway\Model\Logger\Logger;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;

/**
 * Class GetPayMethods
 * @package PayU\PaymentGateway\Model
 */
class GetPayMethods implements PayUGetPayMethodsInterface
{
    /**
     * Test Payment payType
     */
    private const TEST_PAYMENT_CODE = 't';

    private const METHODS_TO_REMOVE_WHEN_ENABLED = [
        PayUSupportedMethods::CODE_INSTALLMENTS => ['ai'],
        PayUSupportedMethods::CODE_KLARNA => ['dpkl', 'dpkleur', 'dpklron', 'dpklhuf'],
        PayUSupportedMethods::CODE_PAYPO => ['dpp', 'dppron'],
        PayUSupportedMethods::CODE_PRAGMA => ['ppf'],
        PayUSupportedMethods::CODE_TWISTO => ['dpt', 'dpcz'],
        PayUSupportedMethods::CODE_TWISTO_SLICE => ['dpts']
    ];

    private \OpenPayU_Retrieve $openPayURetrieve;
    private PayUConfigInterface $payUConfig;
    private GetAvailableLocaleInterface $availableLocale;
    private GatewayConfig $gatewayConfig;
    private int $storeId;
    private Logger $logger;

    private static ?array $result = null;

    public function __construct(
        \OpenPayU_Retrieve $openPayURetrieve,
        PayUConfigInterface $payUConfig,
        GetAvailableLocaleInterface $availableLocale,
        GatewayConfig $gatewayConfig,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->openPayURetrieve = $openPayURetrieve;
        $this->payUConfig = $payUConfig;
        $this->availableLocale = $availableLocale;
        $this->gatewayConfig = $gatewayConfig;
        $this->storeId = $storeManager->getStore()->getId();
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllAvailablePayMethods(?float $totalAmount = null): array {
        $result = [];
        try {
            if (self::$result === null) {
                $this->payUConfig->setDefaultConfig(PayUSupportedMethods::CODE_GATEWAY);
                $response = $this->openPayURetrieve::payMethods($this->availableLocale->execute())->getResponse();

                if (is_array($response->payByLinks)) {
                    $result = $this->sortPaymentMethods($response->payByLinks, $this->payUConfig->getPaymentMethodsOrder());
                }

                self::$result = $result;
            } else {
                $result = self::$result;
            }
        } catch (\OpenPayU_Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }

        if ($totalAmount !== null) {
            $result = $this->filterPayMethodsByAmountAndEnabled($result, $totalAmount);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllPayMethodsForPbl(bool $filterCreditMethods, ?float $totalAmount = null): array {
        if (!$this->isPayMethodActive(PayUSupportedMethods::CODE_GATEWAY)) {
            return [];
        }

        $result = $this->getAllAvailablePayMethods($totalAmount);
        $result = $this->removeTestPayment($result);

        if ($this->isPayMethodActive(PayUSupportedMethods::CODE_CARD)) {
            $result = $this->removePayMethod($result, ['c']);
        }

        if($filterCreditMethods) {
            foreach (self::METHODS_TO_REMOVE_WHEN_ENABLED as $methodCode => $payTypesToRemove) {
                if ($this->isPayMethodActive($methodCode)) {
                    $result = $this->removePayMethod($result, $payTypesToRemove);
                }
            }
        }

        return $result;
    }

    private function isPayMethodActive(string $methodCode): bool
    {
        $this->gatewayConfig->setMethodCode($methodCode);
        return (bool) $this->gatewayConfig->getValue('active', $this->storeId);
    }

    /**
     * Sort Payment Methods
     */
    private function sortPaymentMethods(array $paymentMethods, array $paymentMethodsOrder): array
    {
        if (count($paymentMethodsOrder) < 1) {
            return $paymentMethods;
        }
        array_walk(
            $paymentMethods,
            function ($item, $key, $paymentMethodsOrder) {
                if (array_key_exists($item->value, $paymentMethodsOrder)) {
                    $item->sort = $paymentMethodsOrder[$item->value];
                } else {
                    $item->sort = $key + 100;
                }
            },
            array_flip($paymentMethodsOrder)
        );
        usort(
            $paymentMethods,
            function ($a, $b) {
                return $a->sort - $b->sort;
            }
        );

        return $paymentMethods;
    }

    /**
     * Remove test payment when disabled
     */
    private function removeTestPayment(array $paymethods): array
    {
        return array_values(array_filter(
            $paymethods,
            function ($payByLink) {
                return !($payByLink->value === self::TEST_PAYMENT_CODE && $payByLink->status !== self::PAYMETHOD_STATUS_ENABLED);
            }
        ));
    }

    private function removePayMethod(array $paymethods, array $payTypesToRemove): array
    {
        return array_values(array_filter(
            $paymethods,
            function ($payByLink) use($payTypesToRemove) {
                return !in_array($payByLink->value, $payTypesToRemove);
            }
        ));
    }

    private function filterPayMethodsByAmountAndEnabled(array $paymentMethods, float $totalAmount): array
    {
        return array_values(array_filter(
            $paymentMethods,
            function ($paymentMethod) use ($totalAmount) {
                $minAmount = property_exists($paymentMethod, 'minAmount') ? $paymentMethod->minAmount : null;
                $maxAmount = property_exists($paymentMethod, 'maxAmount') ? $paymentMethod->maxAmount : null;

                if($paymentMethod->status !== self::PAYMETHOD_STATUS_ENABLED) {
                    return false;
                }

                if ($minAmount === null && $maxAmount === null) {
                    return true;
                }

                if ($minAmount !== null && ($totalAmount * 100) < (float) $minAmount) {
                    return false;
                }

                if ($maxAmount !== null && ($totalAmount * 100) > (float) $maxAmount) {
                    return false;
                }

                return true;
            }
        ));
    }
}
