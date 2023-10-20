<?php
namespace PayU\PaymentGateway\Gateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Requests extends AbstractHelper
{
    public function formatAmount(float $amount): string
    {
        return number_format(($amount * 100), 0, '.', '');
    }

    public function getIp(): string
    {
        $ip = $this->_remoteAddress->getRemoteAddress();

        return $ip ?: '127.0.0.1';
    }

    public function getPayuBrowser(): array
    {
        return $this->_request->getParam('payu_browser', []);
    }
}
