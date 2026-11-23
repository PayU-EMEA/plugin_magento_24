<?php

namespace PayU\PaymentGateway\Block;

use Magento\Payment\Block\ConfigurableInfo;
use PayU\PaymentGateway\Api\PayUConfigInterface;

/**
 * Payment info block: renders the PayU data stored on the payment
 * (fields listed in the paymentInfoKeys / privateInfoKeys configuration).
 */
class Info extends ConfigurableInfo
{
    /**
     * @var string
     */
    protected $_template = 'PayU_PaymentGateway::info/default.phtml';

    /**
     * Readable names of the pay method values known by the module,
     * other values (pay-by-links) are displayed as received from PayU
     */
    private const PAY_METHOD_LABELS = [
        'c' => 'Payment card',
        PayUConfigInterface::PAYU_GOOGLE_PAY_METHOD_VALUE => 'Google Pay',
        PayUConfigInterface::PAYU_APPLE_PAY_METHOD_VALUE => 'Apple Pay',
        'blik' => 'BLIK',
        'ai' => 'PayU Installments',
        'dpkl' => 'Klarna',
        'dpkleur' => 'Klarna',
        'dpklron' => 'Klarna',
        'dpklhuf' => 'Klarna',
        'dpklczk' => 'Klarna',
        'dpp' => 'PayPo',
        'dppron' => 'PayPo',
        'ppf' => 'PragmaPay',
        'dpt' => 'Twisto',
        'dpcz' => 'Twisto',
        'dpts' => 'Twisto Pay in 3',
    ];

    private const CARD_SCHEME_LABELS = [
        'MC' => 'Mastercard',
        'VS' => 'Visa',
    ];

    /**
     * PayU logo URL
     */
    public function getLogoUrl(): string
    {
        return $this->getViewFileUrl(PayUConfigInterface::PAYU_BANK_TRANSFER_LOGO_SRC);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabel($field)
    {
        switch ($field) {
            case 'completed_order_id':
                return __('PayU Order ID');
            case PayUConfigInterface::PAYU_TRANSACTION_PAY_METHOD:
                return __('Pay Method');
            case PayUConfigInterface::PAYU_TRANSACTION_CARD_SCHEME:
                return __('Card Type');
            case PayUConfigInterface::PAYU_TRANSACTION_CARD_NUMBER_MASKED:
                return __('Card Number');
            default:
                return parent::getLabel($field);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getValueView($field, $value)
    {
        if ($field === PayUConfigInterface::PAYU_TRANSACTION_PAY_METHOD && isset(self::PAY_METHOD_LABELS[$value])) {
            return __(self::PAY_METHOD_LABELS[$value]);
        }

        if ($field === PayUConfigInterface::PAYU_TRANSACTION_CARD_SCHEME && isset(self::CARD_SCHEME_LABELS[$value])) {
            return self::CARD_SCHEME_LABELS[$value];
        }

        return parent::getValueView($field, $value);
    }
}
