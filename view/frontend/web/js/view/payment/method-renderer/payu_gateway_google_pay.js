/*browser:true*/
/*global define*/
define(
    [
        'paymentGooglePayExtended',
        'ko'
    ],
    function (Component,
              ko
    ) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'PayU_PaymentGateway/payment/payu_gateway_google_pay',
                    postPlaceOrderData: 'payu/data/getPostPlaceOrderData',
                    title: 'Google Pay',
                    logoSrc: undefined,
                    language: undefined,
                    environment: undefined,
                    merchantId: undefined,
                    gatewayMerchantId: undefined,
                    googleMerchantName: undefined,
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

                    const config = (window.checkoutConfig && window.checkoutConfig.payment && window.checkoutConfig.payment.payuGooglePay) || {};

                    this.logoSrc = config.logoSrc;
                    this.language = config.language;
                    this.environment = config.environment;
                    this.merchantId = config.merchantId ? String(config.merchantId).trim() : this.merchantId;
                    this.gatewayMerchantId = config.gatewayMerchantId;
                    this.googleMerchantName = config.googleMerchantName;
                    this.termsUrl = config.termsUrl;

                    return this;
                },


                /**
                 * @return {Object}
                 */
                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            'payu_authorization_code': this.googlePayToken(),
                        }
                    };
                }
            }
        );
    }
);
