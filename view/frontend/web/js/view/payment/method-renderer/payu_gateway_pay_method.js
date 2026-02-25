/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'paymentPblExtended',
        'ko'
    ],
    function ($,
              Component,
              ko
    ) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'PayU_PaymentGateway/payment/payu_gateway_pay_method',
                    postPlaceOrderData: 'payu/data/getPostPlaceOrderData',
                    logoSrc: undefined,
                    language: undefined,
                    isAdditionalInfoVisible: false,
                    enabledStatus: 'ENABLED'
                },

                /**
                 * @return {exports}
                 */
                initialize: function () {
                    this._super();

                    const config = window.checkoutConfig.payment.payuConfig;
                    const language = config.language;
                    const method = config.payMethods[this.item.method];

                    this.title = method.title;
                    this.logoSrc = method.logoSrc;
                    this.language = language;
                    this.isAdditionalInfoVisible = !!method.additionalInfo;
                    return this;
                },

                /**
                 * Override validation for installments (no payuMethod selection required)
                 * @return {Boolean}
                 */
                validate: function () {
                    return true;
                },

                /**
                 * @return {Object}
                 */
                getData: function () {
                    return {
                        'method': this.item.method
                    };
                }
            }
        );
    }
);
