<?php

namespace PayU\PaymentGateway\Controller\Data;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\Logger\Logger;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

class GetApplePayMerchantSession implements HttpGetActionInterface
{
    private ResultFactory $resultFactory;
    private PayUConfigInterface $payUConfig;
    private GatewayConfig $gatewayConfig;
    private Logger $logger;

    public function __construct(
        ResultFactory $resultFactory,
        PayUConfigInterface $payUConfig,
        GatewayConfig $gatewayConfig,
        Logger $logger
    ) {
        $this->resultFactory = $resultFactory;
        $this->payUConfig = $payUConfig;
        $this->gatewayConfig = $gatewayConfig;
        $this->logger = $logger;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $this->payUConfig->setDefaultConfig(PayUSupportedMethods::CODE_APPLE_PAY);
            $merchantSession = $this->resolveMerchantSession();

            return $result->setData($merchantSession);
        } catch (\Throwable $exception) {
            $this->logger->critical('Apple Pay merchant session error', ['exception' => $exception->getMessage()]);

            return $result
                ->setHttpResponseCode(WebapiException::HTTP_BAD_REQUEST)
                ->setData(['message' => 'Unable to validate Apple Pay merchant session']);
        }
    }

    private function resolveMerchantSession(): \stdClass
    {
        $this->gatewayConfig->setMethodCode(PayUSupportedMethods::CODE_APPLE_PAY);
        $domainName = trim((string)$this->gatewayConfig->getValue('apple_domain_name'));
        $displayName = trim((string)$this->gatewayConfig->getValue('apple_store_display_name'));

        if ($domainName === '' || $displayName === '') {
            throw new \RuntimeException('Missing Apple Pay domain or display name');
        }

        try {
            $sessionResult = \OpenPayU_ApplePay::createSession($domainName, $displayName);
        } catch (\OpenPayU_Exception $exception) {
            throw new \RuntimeException('Apple Pay merchant session request failed: ' . $exception->getMessage());
        }

        return $sessionResult->getResponse();
    }
}

