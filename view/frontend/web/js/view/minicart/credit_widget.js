define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'creditWidget'
], function (Component, customerData, creditWidget) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PayU_PaymentGateway/minicart/credit_widget'
        },

        initialize: function () {
            this._super();
            this.cartData = customerData.get('cart');
            this.cartData.subscribe(this.renderWidget.bind(this));

            return this;
        },

        getSubtotalAmount: function () {
            const cart = this.cartData();
            const subtotalAmount = cart && cart.subtotalAmount ? parseFloat(cart.subtotalAmount) : NaN;

            return Number.isFinite(subtotalAmount) ? subtotalAmount : 0;
        },

        renderWidget: function () {
            const config = window.payuMinicartCreditWidgetConfig || {};

            if (!config.isActive) {
                return;
            }

            const amount = this.getSubtotalAmount();

            if (amount <= 0) {
                return;
            }

            creditWidget({
                selector: '#payu-minicart-credit-widget',
                productId: null,
                creditAmount: amount,
                currencySign: config.currencyCode,
                lang: config.lang,
                posId: config.posId,
                key: config.key,
                showLongDescription: true,
                excludedPaytypes: config.excludedPaytypes
            });
        }
    });
});
