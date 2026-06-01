<?php

namespace PayU\PaymentGateway\Plugin\Catalog\Block\Product;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Product;
use PayU\PaymentGateway\ViewModel\CreditWidgetViewModel;

/**
 * Class ListProductPlugin
 * @package PayU\PaymentGateway\Plugin\Catalog\Block\Product
 *
 * @uses Magento\Catalog\Block\Product\ListProduct
 */
class ListProductPlugin
{
    private CreditWidgetViewModel $creditWidgetViewModel;

    public function __construct(CreditWidgetViewModel $creditWidgetViewModel)
    {
        $this->creditWidgetViewModel = $creditWidgetViewModel;
    }

    public function afterGetProductDetailsHtml(ListProduct $subject, $result, Product $product)
    {
        if (!$this->creditWidgetViewModel->isWidgetEnabled('enable_for_catalog')) {
            return $result;
        }

        $resultHtml = is_string($result) ? $result : (string)$result;

        $widgetBlock = $subject->getLayout()->getBlock('payu.credit.widget.simulation');
        if ($widgetBlock) {
            $widgetBlock->setProduct($product);
            $resultHtml .= $widgetBlock->toHtml();
        }

        return $resultHtml;
    }
}
