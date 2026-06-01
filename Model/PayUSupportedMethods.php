<?php

namespace PayU\PaymentGateway\Model;

class PayUSupportedMethods
{
    const CODE_GATEWAY = 'payu_gateway';
    const CODE_CARD = 'payu_gateway_card';
    const CODE_INSTALLMENTS = 'payu_gateway_installments';
    const CODE_KLARNA = 'payu_gateway_klarna';
    const CODE_PAYPO = 'payu_gateway_paypo';
    const CODE_PRAGMA = 'payu_gateway_pragma';
    const CODE_TWISTO = 'payu_gateway_twisto';
    const CODE_TWISTO_SLICE = 'payu_gateway_twisto_slice';

    public static function isSupported(string $methodCode): bool {
        return in_array($methodCode, [
            self::CODE_GATEWAY,
            self::CODE_CARD,
            self::CODE_INSTALLMENTS,
            self::CODE_KLARNA,
            self::CODE_PAYPO,
            self::CODE_PRAGMA,
            self::CODE_TWISTO,
            self::CODE_TWISTO_SLICE
        ], true);
    }
}