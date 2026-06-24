/*browser:true*/
/*global define*/
define(
    [
        'paymentPblExtended',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function (Component,
              ko,
              quote,
              additionalValidators
    ) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'PayU_PaymentGateway/payment/payu_gateway_google_pay',
                    postPlaceOrderData: 'payu/data/getPostPlaceOrderData',
                    logoSrc: undefined,
                    language: undefined,
                    environment: undefined,
                    merchantId: undefined,
                    gatewayMerchantId: undefined,
                    termsUrl: undefined,
                    payuAgreement: ko.observable(true),
                    payuMore1: ko.observable(false),
                    payuMore2: ko.observable(false),
                    googlePayToken: ko.observable(null)
                },

                /**
                 * @return {exports}
                 */
                initialize: function () {
                    this._super();

                    const config = window.checkoutConfig.payment.payuGooglePay;

                    this.title = config && config.title ? config.title : 'Google Pay';
                    this.logoSrc = config && config.logoSrc ? config.logoSrc : undefined;
                    this.language = config && config.language ? config.language : 'en';
                    this.environment = config && config.environment ? config.environment : 'TEST';
                    this.merchantId = config && config.merchantId ? String(config.merchantId).trim() : '';
                    this.gatewayMerchantId = config && config.gatewayMerchantId ? config.gatewayMerchantId : '';
                    this.termsUrl = config && config.termsUrl ? config.termsUrl : '#';
                    this.allowedCardNetworks = config && config.allowedCardNetworks ? config.allowedCardNetworks : ['VISA', 'MASTERCARD'];
                    this.allowedAuthMethods = config && config.allowedAuthMethods ? config.allowedAuthMethods : ['PAN_ONLY', 'CRYPTOGRAM_3DS'];
                    this.pendingOpenGooglePaySheet = false;

                    return this;
                },

                initializeGooglePay: function () {
                    if (this.googlePayClient) {
                        return;
                    }

                    if (!window.google || !window.google.payments) {
                        console.error('Google Pay is not loaded');
                        return;
                    }

                    const merchantInfo = {};

                    if (this.merchantId && this.merchantId.trim() !== '') {
                        merchantInfo.merchantId = this.merchantId;
                    }

                    const googlePayClient = new window.google.payments.api.PaymentsClient({
                        environment: this.environment,
                        merchantInfo: merchantInfo
                    });

                    const clientConfig = {
                        apiVersion: 2,
                        apiVersionMinor: 0,
                        allowedPaymentMethods: [{
                            type: 'CARD',
                            parameters: {
                                allowedAuthMethods: this.allowedAuthMethods,
                                allowedCardNetworks: this.allowedCardNetworks
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
                        console.error('Google Pay client not initialized');
                        return;
                    }

                    const paymentDataRequest = {
                        apiVersion: 2,
                        apiVersionMinor: 0,
                        allowedPaymentMethods: [{
                            type: 'CARD',
                            parameters: {
                                allowedAuthMethods: this.allowedAuthMethods,
                                allowedCardNetworks: this.allowedCardNetworks
                            },
                            tokenizationSpecification: {
                                type: 'PAYMENT_GATEWAY',
                                parameters: {
                                    gateway: 'payu',
                                    gatewayMerchantId: this.gatewayMerchantId
                                }
                            }
                        }],
                        merchantInfo: {
                            merchantId: this.merchantId
                        },
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
                            this.placeOrderWithToken();
                        })
                        .catch(err => {
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

                    if (this.googlePayToken()) {
                        return this.placeOrderWithToken(data, event);
                    }

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
                },

                /**
                 * @return {Object}
                 */
                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            'payu_method': 'ap',
                            'payu_method_type': 'PBL',
                            'payu_authorization_code': this.googlePayToken(),
                        }
                    };
                }
            }
        );
    }
);



