/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList) {
        'use strict';

        const config = window.checkoutConfig.payment;

        if (config.payuGateway.isActive) {
            rendererList.push({
                type: 'payu_gateway',
                component: 'PayU_PaymentGateway/js/view/payment/method-renderer/payu_gateway'
            });
        }

        if (config.payuGatewayCard.isActive) {
            rendererList.push({
                type: 'payu_gateway_card',
                component: 'PayU_PaymentGateway/js/view/payment/method-renderer/payu_gateway_card'
            });
        }

        const payMethods = config.payuConfig.payMethods;

        for (const [payMethodCode, payMethodConfig] of Object.entries(payMethods)) {
            if (payMethodConfig.isActive) {
                rendererList.push({
                    type: payMethodCode,
                    component: 'PayU_PaymentGateway/js/view/payment/method-renderer/payu_gateway_pay_method'
                });
            }
        }

        return Component.extend({});
    }
);
