<?php

namespace PayU\PaymentGateway\Gateway\Http\Client;

class PayUCreateOrder extends PayUAbstractClient
{

    protected function payuApi(array $data): array
    {
        $response = \OpenPayU_Order::create($data)->getResponse();
        return get_object_vars($response);
    }
}
