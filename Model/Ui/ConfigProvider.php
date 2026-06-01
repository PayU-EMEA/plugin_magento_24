<?php

namespace PayU\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayUGetPayMethodsInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Checkout\Model\Session as CheckoutSession;
use PayU\PaymentGateway\Model\PayUSupportedMethods;
use PayU\PaymentGateway\ViewModel\CreditWidgetViewModel;

/**
 * Class ConfigProvider
 *
 * Provide configuration to checkout JS for PayU methods
 */
class ConfigProvider implements ConfigProviderInterface
{
    private PayUGetPayMethodsInterface $payMethods;
    private AssetRepository $assetRepository;
    private GatewayConfig $gatewayConfig;
    private int $storeId;
    private ResolverInterface $resolver;
    private CreditWidgetViewModel $creditWidgetViewModel;
    private CheckoutSession $checkoutSession;

    public function __construct(
        PayUGetPayMethodsInterface $payMethods,
        AssetRepository $assetRepository,
        GatewayConfig $gatewayConfig,
        StoreManagerInterface $storeManager,
        ResolverInterface $resolver,
        CreditWidgetViewModel $creditWidgetViewModel,
        CheckoutSession $checkoutSession
    ) {
        $this->payMethods = $payMethods;
        $this->assetRepository = $assetRepository;
        $this->gatewayConfig = $gatewayConfig;
        $this->storeId = $storeManager->getStore()->getId();
        $this->resolver = $resolver;
        $this->creditWidgetViewModel = $creditWidgetViewModel;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $totalAmount = $quote ? (float) $quote->getGrandTotal() : null;
        $allMethods = $this->payMethods->getAllAvailablePayMethods($totalAmount);

        return [
            'payment' => [
                'payuGateway' => [
                    'isActive' => $this->isPayMethodActive(PayUSupportedMethods::CODE_GATEWAY),
                    'logoSrc' => $this->assetRepository->getUrl(PayUConfigInterface::PAYU_BANK_TRANSFER_LOGO_SRC),
                    'termsUrl' => PayUConfigInterface::PAYU_TERMS_URL,
                    'payByLinks' => $this->payMethods->getAllPayMethodsForPbl(true, $totalAmount),
                    'transferKey' => PayUConfigInterface::PAYU_BANK_TRANSFER_KEY,
                    'language' => $this->getLanguage()
                ],
                'payuConfig' => [
                    'language' => $this->getLanguage(),
                    'creditWidget' => [
                        'isActive' => $this->creditWidgetViewModel->isWidgetEnabled('enable_for_checkout'),
                        'isCartActive' => $this->creditWidgetViewModel->isWidgetEnabled('enable_for_cart'),
                        'posId' => $this->creditWidgetViewModel->getPosId(),
                        'key' => $this->creditWidgetViewModel->getKey(),
                        'excludedPaytypes' => $this->creditWidgetViewModel->getExcludedPaytypes(),
                        'lang' => $this->creditWidgetViewModel->getLanguageCode(),
                        'currency' => $this->creditWidgetViewModel->getCurrencyCode()
                    ],
                    'payMethods' => [
                        PayUSupportedMethods::CODE_INSTALLMENTS => [
                            'isActive' => $this->isPayMethodActive(PayUSupportedMethods::CODE_INSTALLMENTS)
                                && $this->isAnyAvailable($allMethods, ['ai']),
                            'title' => (string) __('Pay with PayU Installments'),
                            'logoSrc' => $this->assetRepository->getUrl('PayU_PaymentGateway::images/payu_installment.svg'),
                            'additionalInfo' => true
                        ],
                        PayUSupportedMethods::CODE_KLARNA => [
                            'isActive' => $this->isPayMethodActive(PayUSupportedMethods::CODE_KLARNA)
                                && $this->isAnyAvailable($allMethods, ['dpkl', 'dpkleur', 'dpklron', 'dpklhuf']),
                            'title' => (string) __('Pay with Klarna'),
                            'logoSrc' => $this->assetRepository->getUrl('PayU_PaymentGateway::images/payu_later_klarna_logo.svg'),
                        ],
                        PayUSupportedMethods::CODE_PAYPO => [
                            'isActive' => $this->isPayMethodActive(PayUSupportedMethods::CODE_PAYPO)
                                && $this->isAnyAvailable($allMethods, ['dpp', 'dppron']),
                            'title' => (string) __('Pay with PayPo'),
                            'logoSrc' => $this->assetRepository->getUrl('PayU_PaymentGateway::images/payu_later_paypo_logo.svg'),
                        ],
                        PayUSupportedMethods::CODE_PRAGMA => [
                            'isActive' => $this->isPayMethodActive(PayUSupportedMethods::CODE_PRAGMA)
                                && $this->isAnyAvailable($allMethods, ['ppf']),
                            'title' => (string) __('Pay with PragmaPay'),
                            'logoSrc' => $this->assetRepository->getUrl('PayU_PaymentGateway::images/payu_pragma_pay.svg'),
                        ],
                        PayUSupportedMethods::CODE_TWISTO => [
                            'isActive' => $this->isPayMethodActive(PayUSupportedMethods::CODE_TWISTO)
                                && $this->isAnyAvailable($allMethods, ['dpt', 'dpcz']),
                            'title' => (string) __('Pay with Twisto'),
                            'logoSrc' => $this->assetRepository->getUrl('PayU_PaymentGateway::images/payu_later_twisto_logo.svg'),
                        ],
                        PayUSupportedMethods::CODE_TWISTO_SLICE => [
                            'isActive' => $this->isPayMethodActive(PayUSupportedMethods::CODE_TWISTO_SLICE)
                                && $this->isAnyAvailable($allMethods, ['dpts']),
                            'title' => (string) __('Pay with Twisto Pay in 3'),
                            'logoSrc' => $this->assetRepository->getUrl('PayU_PaymentGateway::images/payu_twisto_pay_in_3.svg'),
                        ]
                    ]
                ]
            ]
        ];
    }

    private function isAnyAvailable(array $payMethods, array $payTypesToCheck): bool
    {
        foreach ($payMethods as $paymethod) {
            if ($paymethod->status === PayUGetPayMethodsInterface::PAYMETHOD_STATUS_ENABLED
                && in_array($paymethod->value, $payTypesToCheck, true)) {
                return true;
            }
        }
        return false;
    }

    private function isPayMethodActive(string $methodCode): bool
    {
        $this->gatewayConfig->setMethodCode($methodCode);
        return (bool)$this->gatewayConfig->getValue('active', $this->storeId);
    }

    private function getLanguage()
    {
        return current(explode('_', $this->resolver->getLocale()));
    }
}
