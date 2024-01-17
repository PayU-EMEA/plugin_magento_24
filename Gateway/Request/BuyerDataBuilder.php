<?php

namespace PayU\PaymentGateway\Gateway\Request;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class BuyerDataBuilder implements BuilderInterface
{
    private CustomerSession $customerSession;
    private ResolverInterface $resolver;

    public function __construct(
        CustomerSession   $customerSession,
        ResolverInterface $resolver
    )
    {
        $this->customerSession = $customerSession;
        $this->resolver = $resolver;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();

        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $buyer = [];

        $buyer['email'] = $billingAddress === null
            ? $this->customerSession->getCustomer()->getEmail()
            : $billingAddress->getEmail();

        if ($billingAddress !== null) {
            $buyer['firstName'] = $billingAddress->getFirstname();
            $buyer['lastName'] = $billingAddress->getLastname();
            $buyer['phone'] = $billingAddress->getTelephone();
        } elseif ($shippingAddress !== null) {
            $buyer['firstName'] = $shippingAddress->getFirstname();
            $buyer['lastName'] = $shippingAddress->getLastname();
            $buyer['phone'] = $shippingAddress->getTelephone();
        }

        if ($order->getCustomerId() !== null) {
            $buyer['extCustomerId'] = $order->getCustomerId();
        }
        $buyer['language'] = $this->getLanguage();


        if ($shippingAddress !== null) {
            $buyer['delivery'] = [
                'street' => $shippingAddress->getStreetLine1() . ($shippingAddress->getStreetLine2() ? ' ' . $shippingAddress->getStreetLine2() : ''),
                'postalCode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity()
            ];

            if (strlen($shippingAddress->getCountryId()) === 2) {
                $buyer['delivery']['countryCode'] = $shippingAddress->getCountryId();
            }
        }

        return [
            'body' => [
                'buyer' => $buyer
            ]
        ];
    }

    private function getLanguage(): string
    {
        return current(explode('_', $this->resolver->getLocale()));
    }
}
