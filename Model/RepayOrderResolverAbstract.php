<?php

namespace PayU\PaymentGateway\Model;

use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\RepayOrderResolverInterface;
use PayU\PaymentGateway\Gateway\Validator\AbstractResponseValidator;

abstract class RepayOrderResolverAbstract implements RepayOrderResolverInterface
{
    /**
     * Result Success key
     */
    const SUCCESS_FIELD = 'success';

    /**
     * Order ID params
     */
    const ORDER_ID = 'order_id';

    private CommandPoolInterface $commandPool;

    public function __construct(
        CommandPoolInterface $commandPool
    )
    {
        $this->commandPool = $commandPool;
    }


    public function execute(OrderInterface $order, string $method, string $payUMethod, string $payUMethodType, array $payuBrowser): array
    {
        $commandSubject = [
            'method' => $method,
            'order' => $order,
            'payuBrowser' => $payuBrowser
        ];

        if (!empty($payUMethod) && !empty($payUMethodType)) {
            $commandSubject[PayUConfigInterface::PAYU_METHOD_CODE] = $payUMethod;
            $commandSubject[PayUConfigInterface::PAYU_METHOD_TYPE_CODE] = $payUMethodType;
        }

        $this->commandPool->get('repay')->execute($commandSubject);

        $returnData = [static::SUCCESS_FIELD => true];

        $paymentInformation = $order->getPayment()->getAdditionalInformation();
        $statusCode = $paymentInformation[PayUConfigInterface::PAYU_RESPONSE_STATUS];

        if (array_key_exists(PayUConfigInterface::PAYU_REDIRECT_URI_CODE, $paymentInformation) &&
            ($statusCode === \OpenPayU_Order::SUCCESS || $statusCode === AbstractResponseValidator::WARNING_CONTINUE_3_DS)) {
            $returnData[PayUConfigInterface::REDIRECT_URI_FIELD] = $paymentInformation[PayUConfigInterface::PAYU_REDIRECT_URI_CODE];
        } elseif ($statusCode === AbstractResponseValidator::WARNING_CONTINUE_CVV) {
            $this->customerSession->setCvvUrl($paymentInformation[PayUConfigInterface::PAYU_REDIRECT_URI_CODE]);
            $returnData[PayUConfigInterface::REDIRECT_URI_FIELD] = $this->urlBuilder->getUrl(
                'sales/order/continueCvv',
                [static::ORDER_ID => $order->getEntityId()]
            );
        } else {
            $returnData[PayUConfigInterface::REDIRECT_URI_FIELD] =
                $this->urlBuilder->getUrl('sales/order/view', [static::ORDER_ID => $order->getEntityId()]);
        }

        return $returnData;
    }
}
