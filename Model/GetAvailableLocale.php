<?php

namespace PayU\PaymentGateway\Model;

use Magento\Framework\Locale\ResolverInterface;
use PayU\PaymentGateway\Api\GetAvailableLocaleInterface;

class GetAvailableLocale implements GetAvailableLocaleInterface
{
    private ResolverInterface $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $availableLanguages = []): string
    {
        $currentLocale = current(explode('_', $this->resolver->getLocale()));
        if (empty($availableLanguages) || in_array($currentLocale, $availableLanguages)) {

            return current(explode('_', $this->resolver->getLocale()));
        }

        return 'en';
    }
}
