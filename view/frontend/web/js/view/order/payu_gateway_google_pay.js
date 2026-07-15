/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'repayExtended',
        'ko',
        'mage/translate',
        'repay'
    ],
    function (
        $,
        Component,
        ko,
        $t,
        repayModel
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PayU_PaymentGateway/order/payu_gateway_google_pay',
                isChecked: repayModel.method,
                payuAgreement: ko.observable(true),
                payuMore1: ko.observable(false),
                payuMore2: ko.observable(false),
                googlePayToken: ko.observable(null),
                isAvailable: ko.observable(false),
                isPending: ko.observable(false),
                repayErrorMsg: ko.observable(null)
            },

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

            isButtonActiveGooglePay: function () {
                return this.getCode() === this.isChecked() && this.validateGooglePay() && !this.isPending();
            },

            validateGooglePay: function () {
                return this.language === 'pl' ? this.payuAgreement() : true;
            },

            repayGooglePay: function () {
                if (!this.validateGooglePay() || !this.googlePayClient) {
                    return;
                }

                this.openGooglePaySheet();
            },

            openGooglePaySheet: function () {
                var self = this;

                $(document.body).trigger('processStart');
                this.repayErrorMsg(null);
                this.isPending(true);

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

                this.googlePayClient.loadPaymentData(paymentDataRequest).then(function (paymentData) {
                    const token = paymentData?.paymentMethodData?.tokenizationData?.token;

                    if (!token) {
                        self.isPending(false);
                        $(document.body).trigger('processStop');
                        self.repayErrorCallback($t('Something went wrong. Please try again.'));

                        return;
                    }

                    self.googlePayToken(token);
                    self.repay(self.getData());
                }).catch(function (err) {
                    self.isPending(false);
                    $(document.body).trigger('processStop');

                    if (err.statusCode !== 'CANCELED') {
                        console.error('Error loading payment data:', err);
                        self.repayErrorCallback($t('Something went wrong. Please try again.'));
                    }
                });
            },

            getData: function () {
                return {
                    'method': this.getCode(),
                    'order_id': this.orderId,
                    'payu_authorization_code': btoa(this.googlePayToken()),
                    'payu_browser': {
                        'screenWidth': screen.width,
                        'javaEnabled': navigator.javaEnabled(),
                        'timezoneOffset': new Date().getTimezoneOffset(),
                        'screenHeight': screen.height,
                        'userAgent': navigator.userAgent,
                        'colorDepth': screen.colorDepth,
                        'language': navigator.language
                    }
                };
            },

            getTotalPrice: function () {
                return Number(this.amount || 0).toFixed(2);
            },

            getCurrencyCode: function () {
                return String(this.currencyCode || '').toUpperCase();
            }
        });
    }
);

