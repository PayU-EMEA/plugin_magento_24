<?php

namespace PayU\PaymentGateway\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PayU\PaymentGateway\Api\CreateOrderResolverInterface;
use PayU\PaymentGateway\Api\GetAvailableLocaleInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Api\PayUGetMultiCurrencyPricingInterface;
use PayU\PaymentGateway\Api\PayUMcpExchangeRateResolverInterface;

/**
 * Class CreateOrderResolver
 * @package PayU\PaymentGateway\Model
 */
class CreateOrderResolver implements CreateOrderResolverInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var GetAvailableLocaleInterface
     */
    private $availableLocale;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var OrderAdapterInterface
     */
    private $order;

    /**
     * @var PayUGetMultiCurrencyPricingInterface
     */
    private $currencyPricing;

    /**
     * @var PayUMcpExchangeRateResolverInterface
     */
    private $exchangeRateResolver;

    /**
     * @var PayUConfigInterface
     */
    private $payUConfig;

    /**
     * @var Store
     */
    private $store;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $http;

    /**
     * CreateOrderResolver constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param GetAvailableLocaleInterface $availableLocale
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param PayUGetMultiCurrencyPricingInterface $currencyPricing
     * @param PayUMcpExchangeRateResolverInterface $exchangeRateResolver
     * @param PayUConfigInterface $payUConfig
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Request\Http $http
     */
    public function __construct(
        UrlInterface                         $urlBuilder,
        GetAvailableLocaleInterface          $availableLocale,
        CheckoutSession                      $checkoutSession,
        CustomerSession                      $customerSession,
        PayUGetMultiCurrencyPricingInterface $currencyPricing,
        PayUMcpExchangeRateResolverInterface $exchangeRateResolver,
        PayUConfigInterface                  $payUConfig,
        StoreManagerInterface                $storeManager,
        \Magento\Framework\App\Request\Http  $http
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->availableLocale = $availableLocale;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->currencyPricing = $currencyPricing;
        $this->exchangeRateResolver = $exchangeRateResolver;
        $this->payUConfig = $payUConfig;
        $this->store = $storeManager->getStore();
        $this->http = $http;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        OrderAdapterInterface $order,
        Payment               $payment,
                              $methodTypeCode,
                              $methodCode,
                              $browser,
                              $totalDue = null,
                              $orderCurrencyCode = null,
                              $continueUrl = 'checkout/onepage/success'
    )
    {
        $this->order = $order;


        $paymentData = [
            'txn_type' => 'A',
            'description' => $this->getOrderDescription(),
            'customerIp' => $this->getIp(),
            'extOrderId' => $this->getExtOrderId(),
            'totalAmount' => $this->getFormatAmount($this->order->getGrandTotalAmount()),
            'currencyCode' => $this->order->getCurrencyCode(),
            'notifyUrl' => $this->getNotifyUrl($methodTypeCode, $methodCode),
            'continueUrl' => $this->urlBuilder->getUrl($continueUrl),
            'buyer' => $this->getBuyer(),
            'products' => $this->getProductArray($payment),
        ];
        if ($this->isPayUMethod($methodTypeCode, $methodCode)) {
            $paymentData['payMethods']['payMethod'] = ['type' => $methodTypeCode, 'value' => $methodCode];
        }
        if ($orderCurrencyCode === null) {
            $orderCurrencyCode = $this->store->getCurrentCurrencyCode();
        }
        if ($methodTypeCode === PayUConfigInterface::PAYU_CC_TRANSFER_KEY &&
            $this->order->getCurrencyCode() !== $orderCurrencyCode) {
            $paymentData['mcpData'] = $this->getMcpData($totalDue);
        }

        $threeDsAuthentication = $this->getThreeDsAuthentication($paymentData, $browser);

        if ($threeDsAuthentication !== false) {
            $paymentData['threeDsAuthentication'] = $threeDsAuthentication;
        }

        return $paymentData;
    }

    /**
     * Get description for order
     *
     * @return Phrase
     */
    private function getOrderDescription()
    {
        $shopUrl = str_replace('www.', '', parse_url($this->urlBuilder->getBaseUrl(), PHP_URL_HOST));

        return __('Order %1 [%2]', $this->order->getOrderIncrementId(), $shopUrl);
    }

    /**
     * @return array
     */
    private function getBuyer()
    {
        $shippingAddress = $this->order->getShippingAddress();
        $billingAddress = $this->order->getBillingAddress();

        $result['email'] = $billingAddress === null
            ? $this->customerSession->getCustomer()->getEmail()
            : $billingAddress->getEmail();

        if ($billingAddress !== null) {
            $result['firstName'] = $billingAddress->getFirstname();
            $result['lastName'] = $billingAddress->getLastname();
            $result['phone'] = $billingAddress->getTelephone();
        } elseif ($shippingAddress !== null) {
            $result['firstName'] = $shippingAddress->getFirstname();
            $result['lastName'] = $shippingAddress->getLastname();
            $result['phone'] = $shippingAddress->getTelephone();

        }

        $result['extCustomerId'] = $this->order->getCustomerId();
        $result['language'] = $this->availableLocale->execute();


        if ($shippingAddress !== null) {
            $result['delivery'] = [
                'street' => $shippingAddress->getStreetLine1() . ($shippingAddress->getStreetLine2() ? ' ' . $shippingAddress->getStreetLine2() : ''),
                'postalCode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity()
            ];

            if (strlen($shippingAddress->getCountryId()) === 2) {
                $result['delivery']['countryCode'] = $shippingAddress->getCountryId();
            }
        }

        return $result;
    }

    /**
     * @param array $ocrData
     * @param array $browser
     *
     * @return array | null
     */
    private function getThreeDsAuthentication($ocrData, $browser)
    {
        if (!isset($ocrData['payMethods'])
            || $ocrData['payMethods']['payMethod']['type'] === 'CARD_TOKEN'
            || $ocrData['payMethods']['payMethod']['value'] === 'c'
            || $ocrData['payMethods']['payMethod']['value'] === 'ap'
            || $ocrData['payMethods']['payMethod']['value'] === 'jp'
            || $ocrData['payMethods']['payMethod']['value'] === 'ma'
            || $ocrData['payMethods']['payMethod']['value'] === 'vc'
        ) {
            $billingAddress = $this->order->getBillingAddress();

            $threeDsAuthentication = null;

            if ($billingAddress === null) {
                return $threeDsAuthentication;
            }

            $name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
            $address = $billingAddress->getStreetLine1() . ($billingAddress->getStreetLine2() ? ' ' . $billingAddress->getStreetLine2() : '');
            $postalCode = $billingAddress->getPostcode();
            $city = $billingAddress->getCity();
            $countryCode = $billingAddress->getCountryId();

            $isBillingAddress = !empty($address) || !empty($postalCode) || !empty($city) || (!empty($countryCode) && strlen($countryCode) === 2);

            if (!empty($name) || $isBillingAddress) {
                $threeDsAuthentication = [
                    'cardholder' => []
                ];

                if (!empty($name)) {
                    $threeDsAuthentication['cardholder']['name'] = mb_substr($name, 0, 50);
                }

                if ($isBillingAddress) {
                    $threeDsAuthentication['cardholder']['billingAddress'] = [];
                }

                if (!empty($countryCode) && strlen($countryCode) === 2) {
                    $threeDsAuthentication['cardholder']['billingAddress']['countryCode'] = $countryCode;
                }

                if (!empty($address)) {
                    $threeDsAuthentication['cardholder']['billingAddress']['street'] = mb_substr($address, 0, 50);
                }

                if (!empty($city)) {
                    $threeDsAuthentication['cardholder']['billingAddress']['city'] = mb_substr($city, 0, 50);
                }

                if (!empty($postalCode)) {
                    $threeDsAuthentication['cardholder']['billingAddress']['postalCode'] = mb_substr($postalCode, 0, 16);
                }
            }

            if (isset($ocrData['payMethods']['payMethod']['type']) && $ocrData['payMethods']['payMethod']['type'] === 'CARD_TOKEN') {
                $browserData = [
                    'requestIP' => $this->getIP()
                ];

                foreach (PayUConfigInterface::PAYU_BROWSER as $bd) {
                    $browserData[$bd] = $browser[$bd] ?? '';
                }

                if (empty($browserData['userAgent'])) {
                    if ($_SERVER['HTTP_USER_AGENT']) {
                        $browserData['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
                    }
                }

                $threeDsAuthentication['browser'] = $browserData;
            }

            return $threeDsAuthentication;
        }

        return null;
    }

    /**
     * Get external order ID with increment notation
     *
     * @return string
     */
    private function getExtOrderId()
    {
        return $this->order->getOrderIncrementId() . '_' . time();
    }

    /**
     * Get amount in proper format
     *
     * @param $amount
     *
     * @return string
     */
    private function getFormatAmount($amount)
    {
        return number_format(($amount * 100), 0, '.', '');
    }

    /**
     * Get notify url
     *
     * @param string $methodTypeCode
     * @param string $methodCode
     *
     * @return string
     */
    private function getNotifyUrl($methodTypeCode, $methodCode)
    {
        if ($this->isPayUMethod($methodTypeCode, $methodCode)) {
            $parameters['type'] = $methodTypeCode;
        }
        $parameters['store'] = $this->order->getStoreId();

        return $this->urlBuilder->getUrl('payu/data/getNotify', $parameters);
    }

    /**
     * Is order is type of PayU methods
     *
     * @param string $methodTypeCode
     * @param string $methodCode
     *
     * @return bool
     */
    private function isPayUMethod($methodTypeCode, $methodCode)
    {
        return !empty($methodCode) && !empty($methodTypeCode);
    }

    /**
     * Get product array with name, price and product quantity
     *
     * @return array
     */
    private function getProductArray(Payment $payment)
    {
        $list = [];
        $i = 0;

        /** @var Item $product */
        foreach ($this->order->getItems() as $product) {
            if (!$product->isDeleted() && !$product->getParentItemId()) {
                if ($product->getParentItem() === null) {
                    $quantity = $product->getQtyOrdered();
                    $name = $product->getName();

                    if (fmod($quantity, 1) !== 0.0) {
                        $quantity = ceil($quantity);
                        $name = '[' . $product->getQtyOrdered() . '] ' . $name;
                    }

                    if ($quantity === 0) {
                        $quantity = 1;
                    }


                    $list[$i] = [
                        'quantity' => $quantity,
                        'name' => mb_substr($name, 0, 255),
                        'unitPrice' => $this->getFormatAmount($product->getPriceInclTax())
                    ];

                    if ($product->getIsVirtual()) {
                        $list[$i]['virtual'] = true;
                    }
                }
                $i++;
            }
        }

        if ($payment->getOrder()->getShippingMethod() !== null) {
            $list[] = [
                'quantity' => 1,
                'name' => mb_substr('Shipment [' . $payment->getOrder()->getShippingDescription() . ']', 0, 255),
                'unitPrice' => $this->getFormatAmount($payment->getOrder()->getShippingAmount())
            ];
        }

        if ($payment->getOrder()->getDiscountAmount() !== null && $payment->getOrder()->getDiscountAmount() != 0) {
            $list[] = [
                'quantity' => 1,
                'name' => 'Discount',
                'unitPrice' => $this->getFormatAmount($payment->getOrder()->getDiscountAmount())
            ];
        }
    }

    /**
     * Get MCP DATA
     *
     * @param null|float $totalDue
     *
     * @return array
     */
    private function getMcpData($totalDue = null)
    {
        $rates = $this->currencyPricing->execute();
        $mcpRate = $this->exchangeRateResolver->resolve(
            $this->store->getCurrentCurrencyCode(),
            $this->order->getCurrencyCode()
        );

        return [
            'mcpCurrency' => $this->store->getCurrentCurrencyCode(),
            'mcpAmount' => $this->getFormatAmount(
                $totalDue !== null ? $totalDue :
                    $this->checkoutSession->getQuote()->getShippingAddress()->getGrandTotal()
            ),
            'mcpRate' => $mcpRate,
            'mcpFxTableId' => $rates->id,
            'mcpPartnerId' => $this->payUConfig->getMultiCurrencyPricingPartnerId()
        ];
    }

    /**
     * @return string
     */
    private function getIp()
    {
        $ip = explode(',', trim($this->http->getClientIp()));

        return filter_var($ip[0], FILTER_VALIDATE_IP) ? $ip[0] : '127.0.0.1';
    }
}
