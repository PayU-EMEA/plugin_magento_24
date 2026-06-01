define([
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'creditWidget'
], function (Component, quote, creditWidget) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PayU_PaymentGateway/cart/credit_widget'
        },

        /**
         * @return {exports}
         */
        initialize: function () {
            this._super();

            return this;
        },

        getGrandTotal: function () {
            const totals = quote.getTotals()();

            if (totals) {
                return parseFloat(totals['grand_total']);
            }

            return parseFloat(quote['grand_total']);
        },

        render: function () {
            const config = window.checkoutConfig.payment.payuConfig.creditWidget;

            if (config.isActive) {
                creditWidget({
                    selector: '#installment-mini-totals',
                    productId: null,
                    creditAmount: this.getGrandTotal(),
                    currencySign: config.currencyCode,
                    lang: config.lang,
                    posId: config.posId,
                    key: config.key,
                    showLongDescription: true,
                    excludedPaytypes: config.excludedPaytypes
                });
            }
        }
    });
});

