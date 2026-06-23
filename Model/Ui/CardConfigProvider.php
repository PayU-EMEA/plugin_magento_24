<?php

namespace PayU\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayUGetCreditCardSecureFormConfigInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use PayU\PaymentGateway\Api\PayUGetPayMethodsInterface;
use PayU\PaymentGateway\Api\PayUGetUserPayMethodsInterface;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

/**
 * Class ConfigProvider
 *
 * Provide configuration to checkout JS for pay with credit card method
 */
class CardConfigProvider implements ConfigProviderInterface
{
    private PayUGetPayMethodsInterface $payMethods;

    private PayUGetCreditCardSecureFormConfigInterface $secureFormConfig;

    private AssetRepository $assetRepository;

    private GatewayConfig $gatewayConfig;

    private int $storeId;

    private PayUGetUserPayMethodsInterface $userPayMethods;

    private ResolverInterface $resolver;

    private CheckoutSession $checkoutSession;

    public function __construct(
        PayUGetPayMethodsInterface $payMethods,
        PayUGetCreditCardSecureFormConfigInterface $secureFormConfig,
        AssetRepository $assetRepository,
        GatewayConfig $gatewayConfig,
        StoreManagerInterface $storeManager,
        PayUGetUserPayMethodsInterface $userPayMethods,
        ResolverInterface $resolver,
        CheckoutSession $checkoutSession
    ) {
        $this->payMethods = $payMethods;
        $this->secureFormConfig = $secureFormConfig;
        $this->assetRepository = $assetRepository;
        $this->gatewayConfig = $gatewayConfig;
        $this->storeId = $storeManager->getStore()->getId();
        $this->userPayMethods = $userPayMethods;
        $this->resolver = $resolver;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_CARD);
        $configActive = (bool)$this->gatewayConfig->getValue('active', $this->storeId);

        if ($configActive) {
            $quote = $this->checkoutSession->getQuote();
            $totalAmount = $quote ? (float)$quote->getGrandTotal() : null;
            $allMethods = $this->payMethods->getAllAvailablePayMethods($totalAmount);
        } else {
            $allMethods = [];
        }

        $hasCardMethod = (bool) array_filter(
            $allMethods,
            static function ($method): bool {
                return $method->value === 'c';
            }
        );

        $isActive = $configActive && $hasCardMethod;

        $userPayMethods = $isActive ? $this->userPayMethods->execute() : [];

        return [
            'payment' => [
                'payuGatewayCard' => [
                    'isActive' => $isActive,
                    'logoSrc' => $this->assetRepository->getUrl(PayUConfigInterface::PAYU_CC_TRANSFER_LOGO_SRC),
                    'secureFormConfig' => $isActive ? $this->secureFormConfig->execute() : [],
                    'storedCards' => $isActive && array_key_exists(PayUGetUserPayMethodsInterface::CARD_TOKENS, $userPayMethods) && $userPayMethods[PayUGetUserPayMethodsInterface::CARD_TOKENS] ? $userPayMethods[PayUGetUserPayMethodsInterface::CARD_TOKENS] : [],
                    'transferKey' => PayUConfigInterface::PAYU_CC_TRANSFER_KEY,
                    'termsUrl' => PayUConfigInterface::PAYU_TERMS_URL,
                    'language' => $this->getLanguage()
                ]
            ]
        ];
    }

    private function getLanguage()
    {
        return current(explode('_', $this->resolver->getLocale()));
    }
}
