<?php

namespace PayU\PaymentGateway\Controller\Order;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;
use PayU\PaymentGateway\Api\RepaymentResolverInterface;
use PayU\PaymentGateway\Model\Logger\Logger;
use PayU\PaymentGateway\Model\RepayOrderCardResolver;
use PayU\PaymentGateway\Model\RepayOrderResolver;

class Repay implements HttpPostActionInterface
{
    /**
     * Order ID param
     */
    const ORDER_ID = 'order_id';

    /**
     * Result Success key
     */
    const SUCCESS_FIELD = 'success';

    /**
     * Result Error key
     */
    const ERROR_FIELD = 'error';

    /**
     * Error message
     */
    const ERROR_MESASGE = 'Can\'t repay order.';

    private OrderRepository $orderRepository;
    private ResultFactory $resultFactory;
    private RequestInterface $request;
    private Logger $logger;
    private RepayOrderResolver $repayOrderResolver;
    private RepayOrderCardResolver $repayOrderCardResolver;
    private RepaymentResolverInterface $repaymentResolver;

    public function __construct(
        OrderRepository            $orderRepository,
        ResultFactory              $resultFactory,
        RequestInterface           $request,
        Logger                     $logger,
        RepayOrderResolver         $repayOrderResolver,
        RepayOrderCardResolver     $repayOrderCardResolver,
        RepaymentResolverInterface $repaymentResolver
    )
    {
        $this->orderRepository = $orderRepository;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->repayOrderResolver = $repayOrderResolver;
        $this->repayOrderCardResolver = $repayOrderCardResolver;
        $this->repaymentResolver = $repaymentResolver;
    }

    /**
     * (@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $isRepayment = $this->repaymentResolver->isRepayment((int)$this->request->getParam(static::ORDER_ID));
        if (!$isRepayment) {
            return $resultRedirect->setPath('sales/order/history');
        }

        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $returnData = [static::SUCCESS_FIELD => false];
        $orderId = (int)$this->request->getParam(static::ORDER_ID);
        if ($orderId === 0) {
            $returnData[static::ERROR_FIELD] = __('Wrong Request');
            return $result->setData($returnData);
        }

        $method = strip_tags(trim($this->request->getParam('method', '')));

        if ($method === 'payu_gateway') {
            $repayResolver = $this->repayOrderResolver;
        } elseif ($method === 'payu_gateway_card') {
            $repayResolver = $this->repayOrderCardResolver;
        } else {
            $returnData[static::ERROR_FIELD] = __('Wrong Request');
            return $result->setData($returnData);
        }

        $payUMethod = strip_tags(trim($this->request->getParam('payu_method', '')));
        $payUMethodType = strip_tags(trim($this->request->getParam('payu_method_type', '')));
        $payuBrowser = $this->request->getParam('payu_browser', []);

        try {
            $order = $this->orderRepository->get($orderId);
            $returnData = $repayResolver->execute($order, $method, $payUMethod, $payUMethodType, $payuBrowser);
        } catch (NoSuchEntityException $exception) {
            $this->logger->critical($exception->getMessage());
            $returnData[static::ERROR_FIELD] = __(static::ERROR_MESASGE);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $returnData[static::ERROR_FIELD] = __(static::ERROR_MESASGE);
        }

        return $result->setData($returnData);
    }
}
