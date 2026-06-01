<?php

namespace PayU\PaymentGateway\Block\Widget\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\DataObject;
use PayU\PaymentGateway\Model\PayUSupportedMethods;

/**
 * Class TxnId
 * @package PayU\PaymentGateway\Block\Widget\Grid\Column\Renderer
 */
class TxnId extends AbstractRenderer
{
    /**
     * {@inheritdoc}
     */
    protected function _getValue(DataObject $row)
    {
        /** @var Transaction $transaction */
        $transaction = $row;
        $paymentMethod = $transaction->getOrder()->getPayment()->getMethod();
        $paymentId = $transaction->getAdditionalInformation('payment_id');
        if (PayUSupportedMethods::isSupported($paymentMethod) &&
            $transaction->getTxnType() === 'capture' &&
            $paymentId !== null) {
            return $paymentId;
        }

        return parent::_getValue($row);
    }
}
