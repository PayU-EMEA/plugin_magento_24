<?php

namespace PayU\PaymentGateway\Gateway\Http\Client;

class PayUGetOrder extends PayUAbstractClient
{
    protected function payuApi(array $data): array
    {
        $response = \OpenPayU_Order::retrieve($data[self::ORDER_ID])->getResponse();

        if (isset($response->orders) && isset($response->orders[0])) {
            return get_object_vars($response->orders[0]);
        }

        return [];
    }
}
