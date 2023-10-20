<?php

namespace PayU\PaymentGateway\Controller\Data;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Webapi\Exception;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\Ui\CardConfigProvider;
use PayU\PaymentGateway\Model\Ui\ConfigProvider;

class GetNotify implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private PayUConfigInterface $payUConfig;
    private NotifyOrderProcessor $notifyOrderProcessor;
    private NotifyRefundProcessor $notifyRefundProcessor;
    private RawFactory $resultRawFactory;
    private RequestInterface $request;

    public function __construct(
        RawFactory            $resultRawFactory,
        RequestInterface      $request,
        NotifyOrderProcessor  $notifyOrderProcessor,
        NotifyRefundProcessor $notifyRefundProcessor,
        PayUConfigInterface   $payUConfig
    )
    {
        $this->resultRawFactory = $resultRawFactory;
        $this->request = $request;
        $this->notifyOrderProcessor = $notifyOrderProcessor;
        $this->notifyRefundProcessor = $notifyRefundProcessor;
        $this->payUConfig = $payUConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->resultRawFactory->create();

        try {
            $rawBody = trim($this->request->getContent());
            $this->initPayUConfig();
            $payUResultData = \OpenPayU_Order::consumeNotification($rawBody);
            $response = $payUResultData->getResponse();
            if (isset($response->order)) {
                $orderRetrieved = $response->order;
                $this->notifyOrderProcessor->process(
                    $orderRetrieved->status,
                    $orderRetrieved->orderId,
                    $orderRetrieved->totalAmount,
                    $this->getPaymentId($response)
                );
            } else if (isset($response->refund)) {
                $refundRetrieved = $response->refund;
                $this->notifyRefundProcessor->process($refundRetrieved->status, $response->extOrderId);
            }
        } catch (\Exception $exception) {
            $result
                ->setHttpResponseCode(Exception::HTTP_BAD_REQUEST)
                ->setContents($exception->getMessage());
        }

        return $result;
    }

    /**
     * @param \stdClass $response
     *
     * @return null|string
     */
    private function getPaymentId($response)
    {
        if (property_exists($response, 'properties') && is_array($response->properties)) {
            foreach ($response->properties as $property) {
                if ($property->name === 'PAYMENT_ID') {
                    return $property->value;
                }
            }
        }

        return null;
    }

    private function initPayUConfig(): void
    {
        $type = trim(strip_tags($this->request->getParam('type', '')));
        $store = (int)trim(strip_tags($this->request->getParam('store', '')));

        if ($type !== ConfigProvider::CODE && $type !== CardConfigProvider::CODE) {
            throw new \Exception('Unknown type [' . $type . '].');
        }

        $this->payUConfig->setDefaultConfig($type, $store);
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
