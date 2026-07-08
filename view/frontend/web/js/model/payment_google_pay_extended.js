/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (
        $,
        Component,
        quote,
        additionalValidators,
        url,
        fullScreenLoader
    ) {
        'use strict';

        return Component.extend({
            /**
             * @return {exports}
             */
            initialize: function () {
                this._super();
                this.googlePayClient = null;

                this.initializeGooglePay();

                return this;
            },

            initializeGooglePay: function () {
                if (!window.google?.payments?.api?.PaymentsClient) {
                    return;
                }

                this.googlePayClient = new window.google.payments.api.PaymentsClient({
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

                this.googlePayClient.isReadyToPay(clientConfig)
                    .then(response => {
                        if (response.result) {
                            this.isAvailable(true);
                        }
                    })
                    .catch(err => {
                        console.error('Google Pay is not ready:', err);
                    });
            },

            openGooglePaySheet: function () {
                const self = this;

                const paymentDataRequest = {
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    merchantInfo: {
                        merchantId: this.googleMerchantId,
                        merchantName: this.googleMerchantName
                    },
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

                this.googlePayClient.loadPaymentData(paymentDataRequest)
                    .then(paymentData => {
                        const token = paymentData.paymentMethodData.tokenizationData.token;
                        this.googlePayToken(token);

                        this.getPlaceOrderDeferredObject()
                            .fail(
                                function () {
                                    self.isPlaceOrderActionAllowed(true);
                                    fullScreenLoader.stopLoader();
                                }
                            )
                            .done(
                                function () {
                                    self.afterPlaceOrder();

                                    if (self.redirectAfterPlaceOrder) {
                                        $.getJSON(url.build(self.postPlaceOrderData), function (response) {
                                            if (response.success && response.redirectUri) {
                                                window.location.replace(response.redirectUri);
                                            } else {
                                                self.isPlaceOrderActionAllowed(true);
                                                fullScreenLoader.stopLoader();
                                            }
                                        });
                                    }
                                }
                            );
                    })
                    .catch(err => {
                        this.isPlaceOrderActionAllowed(true);

                        if (err.statusCode !== 'CANCELED') {
                            console.error('Error loading payment data:', err);
                        }
                    });
            },

            /**
             * @return {Object}
             */
            getCheckoutTotalsData: function () {
                return quote.totals();
            },

            getTotalPrice: function () {
                const totalPrice = this.getCheckoutTotalsData();

                return Number(totalPrice.base_grand_total).toFixed(2);
            },

            getCurrencyCode: function () {
                const totalPrice = this.getCheckoutTotalsData();

                return String(totalPrice.quote_currency_code).toUpperCase();
            },

            /**
             * @return {Boolean}
             */
            isButtonActive: function () {
                return this.getCode() === this.isChecked() && this.validate() && this.isPlaceOrderActionAllowed();
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                if (this.validate() &&
                    additionalValidators.validate() &&
                    this.isPlaceOrderActionAllowed() === true
                ) {
                    this.isPlaceOrderActionAllowed(false);
                    this.openGooglePaySheet();

                    return true;
                }

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