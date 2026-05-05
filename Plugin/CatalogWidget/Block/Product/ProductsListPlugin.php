<?php

namespace PayU\PaymentGateway\Plugin\CatalogWidget\Block\Product;

use Magento\Catalog\Block\Product\ProductList\Item\Block;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use PayU\PaymentGateway\ViewModel\CreditWidgetViewModel;

/**
 * Class ProductsListPlugin
 * @package PayU\PaymentGateway\Plugin\CatalogWidget\Block\Product
 *
 * @uses Magento\CatalogWidget\Block\Product\ProductsList
 */
class ProductsListPlugin
{
    private CreditWidgetViewModel $creditWidgetViewModel;

    public function __construct(CreditWidgetViewModel $creditWidgetViewModel)
    {
        $this->creditWidgetViewModel = $creditWidgetViewModel;
    }

    public function afterToHtml(ProductsList $subject, $result)
    {
        if (!$this->creditWidgetViewModel->isWidgetEnabled('enable_for_catalog_widgets')) {
            return $result;
        }

        $resultHtml = is_string($result) ? $result : (string)$result;

        $widgetBlock = $subject->getLayout()->createBlock(Template::class);
        $widgetBlock = $widgetBlock->setTemplate('PayU_PaymentGateway::credit_widget/head_script.phtml');
        $widgetBlock->setData('creditWidgetViewModel', $this->creditWidgetViewModel);
        $widgetBlock->setData('configKey', 'enable_for_catalog_widgets');

        $resultHtml = $widgetBlock->toHtml() . $resultHtml;

        return $resultHtml;
    }

    public function afterGetProductDetailsHtml(ProductsList $subject, $result, Product $product)
    {
        if (!$this->creditWidgetViewModel->isWidgetEnabled('enable_for_catalog_widgets')) {
            return $result;
        }

        $resultHtml = is_string($result) ? $result : (string)$result;

        $widgetBlock = $subject->getLayout()->createBlock(Block::class);
        $widgetBlock = $widgetBlock->setTemplate('PayU_PaymentGateway::product/credit_widget.phtml');
        $widgetBlock->setData('creditWidgetViewModel', $this->creditWidgetViewModel);
        $widgetBlock->setData('configKey', 'enable_for_catalog_widgets');
        $widgetBlock->setProduct($product);

        $resultHtml .= $widgetBlock->toHtml();

        return $resultHtml;
    }
}
