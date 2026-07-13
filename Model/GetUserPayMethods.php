<?php

namespace PayU\PaymentGateway\Model;

use PayU\PaymentGateway\Api\PayUGetUserPayMethodsInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\GetAvailableLocaleInterface;
use PayU\PaymentGateway\Model\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;

class GetUserPayMethods implements PayUGetUserPayMethodsInterface
{
    private \OpenPayU_Retrieve $openPayURetrieve;
    private PayUConfigInterface $payUConfig;
    private GetAvailableLocaleInterface $availableLocale;
    private Session $checkoutSession;
    private CustomerSession $customerSession;
    private array $result = [];
    private Logger $logger;

    public function __construct(
        \OpenPayU_Retrieve $openPayURetrieve,
        PayUConfigInterface $payUConfig,
        GetAvailableLocaleInterface $availableLocale,
        Session $checkoutSession,
        CustomerSession $customerSession,
        Logger $logger
    ) {
        $this->openPayURetrieve = $openPayURetrieve;
        $this->payUConfig = $payUConfig;
        $this->availableLocale = $availableLocale;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(?string $email = null, ?int $customerId = null): array
    {
        $customerEmail = $email ?? $this->checkoutSession->getQuote()->getCustomerEmail();
        if (!$this->payUConfig->isStoreCardEnable() ||
            $customerEmail === null ||
            !$this->customerSession->isLoggedIn()) {
            return [];
        }
        try {
            $this->payUConfig
                ->setDefaultConfig(PayUSupportedMethods::CODE_CARD)
                ->setOauthGrantType(PayUConfigInterface::GRANT_TYPE_TRUSTED_MERCHANT)
                ->setOauthEmail($customerEmail)
                ->setCustomerExtId($customerId ?? $this->customerSession->getCustomerId());
            $payURetrieve = $this->openPayURetrieve;
            $response = $payURetrieve::payMethods($this->availableLocale->execute())->getResponse();
            if (isset($response->cardTokens)) {
                $this->result = [
                    static::CARD_TOKENS => $response->cardTokens
                ];
            }
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $this->result = [];
        }

        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson(): string
    {
        return json_encode($this->result, JSON_THROW_ON_ERROR);
    }

}
