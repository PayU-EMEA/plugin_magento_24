<?php

declare(strict_types=1);

namespace PayU\PaymentGateway\Controller\Data;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\Logger\Logger;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

class GetApplePayMerchantSession implements HttpGetActionInterface
{
    private const HTTP_BAD_REQUEST = 400;

    private ResultFactory $resultFactory;
    private RequestInterface $request;
    private PayUConfigInterface $payUConfig;
    private Logger $logger;

    public function __construct(
        ResultFactory $resultFactory,
        RequestInterface $request,
        PayUConfigInterface $payUConfig,
        Logger $logger
    ) {
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->payUConfig = $payUConfig;
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
                ->setHttpResponseCode(self::HTTP_BAD_REQUEST)
                ->setData(['message' => 'Unable to validate Apple Pay merchant session']);
        }
    }

    private function resolveMerchantSession(): array
    {
        $domainName = trim((string)$this->request->getParam('domainName', ''));
        $displayName = trim((string)$this->request->getParam('displayName', ''));

        if ($domainName === '' || $displayName === '') {
            throw new \RuntimeException('Missing Apple Pay domain or display name');
        }

        if (!class_exists('\\OpenPayU_ApplePay')) {
            throw new \RuntimeException('Apple Pay session API is unavailable in OpenPayU SDK');
        }

        $sessionResult = \OpenPayU_ApplePay::createSession($domainName, $displayName);

        if ($sessionResult->getStatus() !== 'SUCCESS') {
            throw new \RuntimeException($sessionResult->getError() ?: 'Apple Pay merchant session request failed');
        }

        $merchantSession = json_decode((string)json_encode($sessionResult->getResponse()), true);

        if (!is_array($merchantSession) || empty($merchantSession)) {
            throw new \RuntimeException('Empty Apple Pay merchant session response');
        }

        return $merchantSession;
    }
}

