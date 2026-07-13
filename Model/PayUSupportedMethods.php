<?php

namespace PayU\PaymentGateway\Model;

class PayUSupportedMethods
{
    public const CODE_GATEWAY = 'payu_gateway';
    public const CODE_CARD = 'payu_gateway_card';
    public const CODE_GOOGLE_PAY = 'payu_gateway_google_pay';
    public const CODE_INSTALLMENTS = 'payu_gateway_installments';
    public const CODE_KLARNA = 'payu_gateway_klarna';
    public const CODE_PAYPO = 'payu_gateway_paypo';
    public const CODE_PRAGMA = 'payu_gateway_pragma';
    public const CODE_TWISTO = 'payu_gateway_twisto';
    public const CODE_TWISTO_SLICE = 'payu_gateway_twisto_slice';

    public static function isSupported(string $methodCode): bool {
        return in_array($methodCode, [
            self::CODE_GATEWAY,
            self::CODE_CARD,
            self::CODE_GOOGLE_PAY,
            self::CODE_INSTALLMENTS,
            self::CODE_KLARNA,
            self::CODE_PAYPO,
            self::CODE_PRAGMA,
            self::CODE_TWISTO,
            self::CODE_TWISTO_SLICE,
        ], true);
    }
}
