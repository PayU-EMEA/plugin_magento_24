define([
    'Magento_Checkout/js/model/quote'
], function (quote) {
    'use strict';

    return function (Component) {
        return Component.extend({
            /**
             * PayU charges in order currency, so the base currency charge info is misleading.
             *
             * @return {Boolean}
             */
            isBaseGrandTotalDisplayNeeded() {
                if (quote.paymentMethod()?.method?.startsWith('payu_gateway')) {
                    return false;
                }

                return this._super();
            }
        });
    };
});
