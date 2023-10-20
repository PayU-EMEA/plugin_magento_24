<?php

namespace PayU\PaymentGateway\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Magento\Sales\Controller\AbstractController\View;
use PayU\PaymentGateway\Api\RepaymentResolverInterface;

class RepayView extends View
{
    private RepaymentResolverInterface $repaymentResolver;

    public function __construct(
        Context                    $context,
        OrderLoaderInterface       $orderLoader,
        PageFactory                $resultPageFactory,
        RepaymentResolverInterface $repaymentResolver
    )
    {
        $this->repaymentResolver = $repaymentResolver;
        parent::__construct($context, $orderLoader, $resultPageFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $isRepayment = $this->repaymentResolver->isRepayment((int)$this->getRequest()->getParam('order_id'));
        if (!$isRepayment) {
            return $this->resultRedirectFactory->create()->setPath('sales/order/history');
        }

        return parent::execute();
    }
}
