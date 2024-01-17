<?php

namespace PayU\PaymentGateway\Gateway\Helper;

use Magento\Sales\Api\Data\OrderInterface;

class RepaySubjectReader
{
    /**
     * Reads order object from subject
     */
    public static function readOrder(array $subject): OrderInterface
    {
        if (!isset($subject['order'])
            || !$subject['order'] instanceof OrderInterface
        ) {
            throw new \InvalidArgumentException('Order object should be provided');
        }

        return $subject['order'];
    }

    /**
     * Reads method from subject
     */
    public static function readMethod(array $subject): string
    {
        if (!isset($subject['method']) || !is_string($subject['method'])) {
            throw new \InvalidArgumentException('Method should be provided');
        }

        return $subject['method'];
    }

    /**
     * Reads payu method from subject
     */
    public static function readPayuMethod(array $subject): ?string
    {
        return $subject['payu_method'] ?? null;
    }

    /**
     * Reads payu method from subject
     */
    public static function readPayuMethodType(array $subject): ?string
    {
        return $subject['payu_method_type'] ?? null;
    }

    /**
     * Reads payu_browser from subject
     */
    public static function readPayuBrowser(array $subject): array
    {
        if (!isset($subject['payuBrowser']) || !is_array($subject['payuBrowser'])) {
            throw new \InvalidArgumentException('Payu Browser should be provided');
        }

        return $subject['payuBrowser'];
    }

}
