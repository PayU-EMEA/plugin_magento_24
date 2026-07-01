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

      const config = (window.checkoutConfig && window.checkoutConfig.payment && window.checkoutConfig.payment.payuGooglePay) || {};

        return Component.extend(
            {
                defaults: {
                    template: 'PayU_PaymentGateway/payment/payu_gateway_google_pay',
                    postPlaceOrderData: 'payu/data/getPostPlaceOrderData',
                    logoSrc: config.logoSrc,
                    language: config.language,
                    environment: config.environment,
                    merchantId: String(config.merchantId).trim(),
                    gatewayMerchantId: config.gatewayMerchantId,
                    googleMerchantName: config.googleMerchantName,
                    termsUrl: config.termsUrl,
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

                    return this;
                },


                /**
                 * @return {Object}
                 */
                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            'payu_authorization_code': btoa(this.googlePayToken()),
                        }
                    };
                }
            }
        );
    }
);
