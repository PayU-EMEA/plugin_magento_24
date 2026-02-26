define([
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'creditWidget'
], function (Component, quote, totalsService, creditWidget) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PayU_PaymentGateway/cart/credit_widget_totals'
        },

        initialize: function () {
            this._super();
            totalsService.totals.subscribe(this.render.bind(this));

            return this;
        },

        getGrandTotal: function () {
            const totals = totalsService.totals() || quote.getTotals()();

            if (totals) {
                return parseFloat(totals['grand_total']);
            }

            return parseFloat(quote['grand_total']);
        },

        render: function () {
            const config = window.checkoutConfig.payment.payuConfig.creditWidget;
            const grandTotal = this.getGrandTotal();

            if (!config.isCartActive) {
                return;
            }

            creditWidget({
                selector: '#installment-mini-totals',
                productId: null,
                creditAmount: grandTotal,
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
