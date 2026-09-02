/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'repayExtended',
        'ko',
        'mage/translate',
        'mage/url',
        'repay'
    ],
    function (
        $,
        Component,
        ko,
        $t,
        url,
        repayModel
    ) {
        'use strict';

        const APPLE_PAY_API_MAX_VERSION = 14;
        const APPLE_PAY_SUCCESS = window.ApplePaySession ? window.ApplePaySession.STATUS_SUCCESS : 1;
        const APPLE_PAY_FAILURE = window.ApplePaySession ? window.ApplePaySession.STATUS_FAILURE : 2;

        return Component.extend({
            defaults: {
                template: 'PayU_PaymentGateway/order/payu_gateway_apple_pay',
                applePaySessionUrl: 'payu/data/getApplePayMerchantSession',
                isChecked: repayModel.method,
                payuAgreement: ko.observable(true),
                payuMore1: ko.observable(false),
                payuMore2: ko.observable(false),
                applePayToken: ko.observable(null),
                isAvailable: ko.observable(false),
                isPending: ko.observable(false),
                repayErrorMsg: ko.observable(null)
            },

            initialize: function () {
                this._super();
                this.applePaySession = null;
                this.initializeApplePay();

                return this;
            },

            initializeApplePay: function () {
                if (!window.ApplePaySession) {
                    return;
                }

                try {
                    if (!window.ApplePaySession.canMakePayments()) {
                        return;
                    }
                } catch (e) {
                    console.error('Apple Pay is not ready:', e);

                    return;
                }

                this.isAvailable(true);
            },

            getApplePayApiVersion: function () {
                let apiVersion = 1;

                for (let i = APPLE_PAY_API_MAX_VERSION; i > 1; i--) {
                    if (window.ApplePaySession.supportsVersion(i)) {
                        apiVersion = i;
                        break;
                    }
                }

                return apiVersion;
            },

            isButtonActiveApplePay: function () {
                return this.getCode() === this.isChecked() && this.validateApplePay() && !this.isPending();
            },

            validateApplePay: function () {
                return this.language === 'pl' ? this.payuAgreement() : true;
            },

            repayApplePay: function () {
                if (!this.validateApplePay()) {
                    return;
                }

                this.openApplePaySheet();
            },

            getPaymentRequest: function () {
                return {
                    merchantCapabilities: ['supports3DS', 'supportsCredit', 'supportsDebit'],
                    supportedNetworks: ['masterCard', 'visa'],
                    countryCode: 'PL',
                    currencyCode: this.getCurrencyCode(),
                    total: {
                        type: 'final',
                        label: this.displayName,
                        amount: this.getTotalPrice()
                    }
                };
            },

            openApplePaySheet: function () {
                const self = this;

                $(document.body).trigger('processStart');
                this.repayErrorMsg(null);
                this.isPending(true);

                try {
                    this.applePaySession = new window.ApplePaySession(
                        this.getApplePayApiVersion(),
                        this.getPaymentRequest()
                    );
                } catch (e) {
                    console.error('Cannot initialize Apple Pay session:', e);
                    this.repayErrorCallback($t('Something went wrong. Please try again.'));
                    this.isPending(false);
                    $(document.body).trigger('processStop');

                    return;
                }

                this.applePaySession.onvalidatemerchant = function () {
                    $.getJSON(url.build(self.applePaySessionUrl), {
                        domainName: self.domainName,
                        displayName: self.displayName
                    })
                        .done(function (response) {
                            try {
                                self.applePaySession.completeMerchantValidation(response);
                            } catch (error) {
                                console.error('Apple Pay merchant validation failed:', error);
                                self.applePaySession.abort();
                                self.repayErrorCallback($t('Something went wrong. Please try again.'));
                                self.isPending(false);
                                $(document.body).trigger('processStop');
                            }
                        })
                        .fail(function (error) {
                            console.error('Apple Pay session request failed:', error);
                            self.applePaySession.abort();
                            self.repayErrorCallback($t('Something went wrong. Please try again.'));
                            self.isPending(false);
                            $(document.body).trigger('processStop');
                        });
                };

                this.applePaySession.onpaymentauthorized = function (event) {
                    let token = null;

                    try {
                        token = JSON.stringify(event.payment.token.paymentData);
                    } catch (error) {
                        console.error('Apple Pay token conversion failed:', error);
                    }

                    if (!token) {
                        self.applePaySession.completePayment(APPLE_PAY_FAILURE);
                        self.repayErrorCallback($t('Something went wrong. Please try again.'));
                        self.isPending(false);
                        $(document.body).trigger('processStop');

                        return;
                    }

                    self.applePayToken(token);
                    self.repayWithApplePay(self.applePaySession);
                };

                this.applePaySession.oncancel = function () {
                    self.isPending(false);
                    $(document.body).trigger('processStop');
                };

                this.applePaySession.begin();
            },

            repayWithApplePay: function (applePaySession) {
                var self = this;

                $.ajax({
                    url: url.build(self.repayUrl),
                    data: self.getData(),
                    dataType: 'json',
                    type: 'POST',
                    success: function (response) {
                        if (response.success && response.redirectUri) {
                            applePaySession.completePayment(APPLE_PAY_SUCCESS);
                            window.location.replace(response.redirectUri);
                        } else {
                            applePaySession.completePayment(APPLE_PAY_FAILURE);
                            $(document.body).trigger('processStop');
                            self.repayErrorCallback(response.error);
                        }
                    },
                    error: function () {
                        applePaySession.completePayment(APPLE_PAY_FAILURE);
                        $(document.body).trigger('processStop');
                        self.repayErrorCallback();
                    },
                    complete: function () {
                        self.isPending(false);
                    }
                });
            },

            getData: function () {
                return {
                    'method': this.getCode(),
                    'order_id': this.orderId,
                    'payu_authorization_code': btoa(this.applePayToken()),
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

