/*browser:true*/
/*global define*/
define(
    [
        'paymentPblExtended',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function (
        Component,
        quote,
        additionalValidators
    ) {
        'use strict';

        return Component.extend({
            /**
             * @return {exports}
             */
            initialize: function () {
                this._super();
                this.pendingOpenGooglePaySheet = false;

                return this;
            },

            initializeGooglePay: function () {
                if (this.googlePayClient) {
                    if (this.pendingOpenGooglePaySheet) {
                        this.pendingOpenGooglePaySheet = false;
                        this.openGooglePaySheet();
                    }

                    return;
                }

                if (!window.google || !window.google.payments) {
                    this.pendingOpenGooglePaySheet = false;
                    console.error('Google Pay is not loaded');
                    return;
                }

                const googlePayClient = new window.google.payments.api.PaymentsClient({
                    environment: this.environment
                });

                const clientConfig = {
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    allowedPaymentMethods: [{
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                            allowedCardNetworks: ['VISA', 'MASTERCARD']
                        }
                    }]
                };

                googlePayClient.isReadyToPay(clientConfig)
                    .then(response => {
                        if (response.result) {
                            this.googlePayClient = googlePayClient;

                            if (this.pendingOpenGooglePaySheet) {
                                this.pendingOpenGooglePaySheet = false;
                                this.openGooglePaySheet();
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Google Pay is not ready:', err);
                    });
            },

            openGooglePaySheet: function () {
                if (!this.googlePayClient) {
                    this.pendingOpenGooglePaySheet = false;
                    console.error('Google Pay client not initialized');
                    return;
                }

                const paymentDataRequest = {
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    allowedPaymentMethods: [{
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                            allowedCardNetworks: ['VISA', 'MASTERCARD']
                        },
                        tokenizationSpecification: {
                            type: 'PAYMENT_GATEWAY',
                            parameters: {
                                gateway: 'payu',
                                gatewayMerchantId: this.gatewayMerchantId
                            }
                        }
                    }],
                    transactionInfo: {
                        totalPriceStatus: 'FINAL',
                        totalPrice: this.getTotalPrice(),
                        currencyCode: this.getCurrencyCode(),
                        countryCode: 'PL'
                    }
                };

                if (this.merchantId && this.merchantId.trim() !== '') {
                    paymentDataRequest.merchantInfo = {
                        merchantId: this.merchantId,
                        merchantName: this.googleMerchantName
                    };
                }

                this.googlePayClient.loadPaymentData(paymentDataRequest)
                    .then(paymentData => {
                        const token = paymentData.paymentMethodData.tokenizationData.token;
                        this.googlePayToken(token);
                        this.placeOrderWithToken();
                    })
                    .catch(err => {
                        this.googlePayToken(null);

                        if (err.statusCode !== 'CANCELED') {
                            console.error('Error loading payment data:', err);
                        }
                    });
            },

            /**
             * @return {Object}
             */
            getCheckoutTotalsData: function () {
                const quoteTotals = quote && typeof quote.totals === 'function' ? quote.totals() : null;

                if (quoteTotals && typeof quoteTotals === 'object') {
                    return quoteTotals;
                }

                const quoteData = window.checkoutConfig && window.checkoutConfig.quoteData
                    ? window.checkoutConfig.quoteData
                    : null;

                if (quoteData && typeof quoteData === 'object') {
                    return quoteData;
                }

                return {};
            },

            getTotalPrice: function () {
                const totalPrice = this.getCheckoutTotalsData();
                const grandTotal = totalPrice.grand_total || totalPrice.base_grand_total;

                return grandTotal ? Number(grandTotal).toFixed(2) : '0.00';
            },

            getCurrencyCode: function () {
                const totalPrice = this.getCheckoutTotalsData();
                const currency = totalPrice.quote_currency_code || totalPrice.base_currency_code;

                return currency ? String(currency).toUpperCase() : 'PLN';
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                if (!this.validate() || !additionalValidators.validate() || this.isPlaceOrderActionAllowed() !== true) {
                    return false;
                }

                // Google Pay token should be collected fresh for each order attempt.
                this.googlePayToken(null);

                this.pendingOpenGooglePaySheet = true;
                this.initializeGooglePay();

                return false;
            },

            placeOrderWithToken: function (data, event) {
                return Component.prototype.placeOrder.call(this, data, event);
            },

            /**
             * @return {Boolean}
             */
            validate: function () {
                return this.language === 'pl' ? this.payuAgreement() : true;
            }
        });
    }
);

/*browser:true*/
/*global define*/
define(
    [
        'paymentPblExtended',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function (
        Component,
        quote,
        additionalValidators
    ) {
        'use strict';

        return Component.extend({
            /**
             * @return {exports}
             */
            initialize: function () {
                this._super();
                this.pendingOpenGooglePaySheet = false;

                return this;
            },

            initializeGooglePay: function () {
                if (this.googlePayClient) {
                    if (this.pendingOpenGooglePaySheet) {
                        this.pendingOpenGooglePaySheet = false;
                        this.openGooglePaySheet();
                    }

                    return;
                }

                if (!window.google || !window.google.payments) {
                    this.pendingOpenGooglePaySheet = false;
                    console.error('Google Pay is not loaded');
                    return;
                }

                const googlePayClient = new window.google.payments.api.PaymentsClient({
                    environment: this.environment
                });

                const clientConfig = {
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    allowedPaymentMethods: [{
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                            allowedCardNetworks: ['VISA', 'MASTERCARD']
                        }
                    }]
                };

                googlePayClient.isReadyToPay(clientConfig)
                    .then(response => {
                        if (response.result) {
                            this.googlePayClient = googlePayClient;

                            if (this.pendingOpenGooglePaySheet) {
                                this.pendingOpenGooglePaySheet = false;
                                this.openGooglePaySheet();
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Google Pay is not ready:', err);
                    });
            },

            openGooglePaySheet: function () {
                if (!this.googlePayClient) {
                    this.pendingOpenGooglePaySheet = false;
                    console.error('Google Pay client not initialized');
                    return;
                }

                const paymentDataRequest = {
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    allowedPaymentMethods: [{
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                            allowedCardNetworks: ['VISA', 'MASTERCARD']
                        },
                        tokenizationSpecification: {
                            type: 'PAYMENT_GATEWAY',
                            parameters: {
                                gateway: 'payu',
                                gatewayMerchantId: this.gatewayMerchantId
                            }
                        }
                    }],
                    transactionInfo: {
                        totalPriceStatus: 'FINAL',
                        totalPrice: this.getTotalPrice(),
                        currencyCode: this.getCurrencyCode(),
                        countryCode: 'PL'
                    }
                };

                if (this.merchantId && this.merchantId.trim() !== '') {
                    paymentDataRequest.merchantInfo = {
                        merchantId: this.merchantId,
                        merchantName: this.googleMerchantName
                    };
                }

                this.googlePayClient.loadPaymentData(paymentDataRequest)
                    .then(paymentData => {
                        const token = paymentData.paymentMethodData.tokenizationData.token;
                        this.googlePayToken(token);
                        this.placeOrderWithToken();
                    })
                    .catch(err => {
                        this.googlePayToken(null);

                        if (err.statusCode !== 'CANCELED') {
                            console.error('Error loading payment data:', err);
                        }
                    });
            },

            /**
             * @return {Object}
             */
            getCheckoutTotalsData: function () {
                const quoteTotals = quote && typeof quote.totals === 'function' ? quote.totals() : null;

                if (quoteTotals && typeof quoteTotals === 'object') {
                    return quoteTotals;
                }

                const quoteData = window.checkoutConfig && window.checkoutConfig.quoteData
                    ? window.checkoutConfig.quoteData
                    : null;

                if (quoteData && typeof quoteData === 'object') {
                    return quoteData;
                }

                return {};
            },

            getTotalPrice: function () {
                const totalPrice = this.getCheckoutTotalsData();
                const grandTotal = totalPrice.grand_total || totalPrice.base_grand_total;

                return grandTotal ? Number(grandTotal).toFixed(2) : '0.00';
            },

            getCurrencyCode: function () {
                const totalPrice = this.getCheckoutTotalsData();
                const currency = totalPrice.quote_currency_code || totalPrice.base_currency_code;

                return currency ? String(currency).toUpperCase() : 'PLN';
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                if (!this.validate() || !additionalValidators.validate() || this.isPlaceOrderActionAllowed() !== true) {
                    return false;
                }

                // Google Pay token should be collected fresh for each order attempt.
                this.googlePayToken(null);

                this.pendingOpenGooglePaySheet = true;
                this.initializeGooglePay();

                return false;
            },

            placeOrderWithToken: function (data, event) {
                return Component.prototype.placeOrder.call(this, data, event);
            },

            /**
             * @return {Boolean}
             */
            validate: function () {
                return this.language === 'pl' ? this.payuAgreement() : true;
            }
        });
    }
);

