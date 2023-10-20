<?php

namespace PayU\PaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;

class TransferFactory implements TransferFactoryInterface
{
    private TransferBuilder $transferBuilder;

    public function __construct(TransferBuilder $transferBuilder)
    {
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $request)
    {
        return $this->transferBuilder
            ->setBody($request['body'])
            ->setClientConfig($request['clientConfig'])
            ->build();
    }
}
