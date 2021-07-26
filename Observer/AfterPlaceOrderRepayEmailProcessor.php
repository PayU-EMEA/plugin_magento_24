<?php
namespace PayU\PaymentGateway\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Class AfterPlaceOrderRepayProcessor
 * @package PayU\PaymentGateway\Observer
 */
class AfterPlaceOrderRepayEmailProcessor
{
    /**
     * Store key
     */
    const STORE = 'store';

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var Renderer
     */
    protected $addressRenderer;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * AfterPlaceOrderRepayProcessor constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentHelper $paymentHelper
     * @param Renderer $addressRenderer
     * @param TransportBuilder $transportBuilder
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        PaymentHelper $paymentHelper,
        Renderer $addressRenderer,
        TransportBuilder $transportBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->paymentHelper = $paymentHelper;
        $this->addressRenderer = $addressRenderer;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Send Repay Email
     *
     * @param Payment $payment
     *
     * @return void
     */
    public function process(Payment $payment)
    {
        $order = $payment->getOrder();
        $store = $order->getStore();
        $emailTempVariables = [
            'repay_url' => $this->urlBuilder->getUrl(
                'sales/order/repayview',
                [
                    'order_id' => $order->getId(),
                    'hash' => md5($order->getCustomerEmail() . $order->getId() . $order->getCreatedAt())
                ]
            ),
            'order' => $order,
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order, $store),
            'store' => $store,
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'created_at_formatted' => $order->getCreatedAtFormatted(2),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ]
        ];
        $sender = $this->scopeConfig->getValue(
            'sales_email/order/identity',
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );

        $transport = $this->transportBuilder->setTemplateIdentifier('repay_email_template')
            ->setTemplateOptions(
            [
                'area' => Area::AREA_FRONTEND,
                static::STORE => $store->getId()
            ])
            ->setTemplateVars($emailTempVariables)
            ->setFromByScope($sender, $store->getId())
            ->addTo($order->getCustomerEmail())
            ->getTransport();

        $transport->sendMessage();
    }

    /**
     * Get payment info block as html
     *
     * @param Order $order
     * @param Store $store
     * @return string
     */
    private function getPaymentHtml(Order $order, Store $store)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $store->getId()
        );
    }
    /**
     * Render shipping address into html.
     *
     * @param Order $order
     * @return string|null
     */
    private function getFormattedShippingAddress($order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * Render billing address into html.
     *
     * @param Order $order
     * @return string|null
     */
    private function getFormattedBillingAddress($order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }
}
