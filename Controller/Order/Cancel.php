<?php

namespace PayU\PaymentGateway\Controller\Order;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Cancel implements HttpGetActionInterface
{
    private ResultFactory $resultFactory;
    private Session $checkoutSession;
    public function __construct(
        ResultFactory $resultFactory,
        Session $checkoutSession
    ) {
        $this->resultFactory   = $resultFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * (@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($this->checkoutSession->restoreQuote()) {
            return $resultRedirect->setPath('checkout', ['_fragment' => 'payment']);
        } else {
            return $resultRedirect->setPath('');
        }
    }
}
