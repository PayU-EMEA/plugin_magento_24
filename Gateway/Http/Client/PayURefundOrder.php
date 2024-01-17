<?php

namespace PayU\PaymentGateway\Gateway\Http\Client;

class PayURefundOrder extends PayUAbstractClient
{

    protected function payuApi(array $data): array
    {
        $response = \OpenPayU_Refund::create($data[PayUAbstractClient::ORDER_ID], $data[PayUAbstractClient::DESCRIPTION], $data[PayUAbstractClient::AMOUNT]);

        return ['status' => $response->getStatus()];
    }
}
