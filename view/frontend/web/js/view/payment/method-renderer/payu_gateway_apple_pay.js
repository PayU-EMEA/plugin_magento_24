/*browser:true*/
/*global define*/
define(
    [
      'paymentApplePayExtended',
      'ko'
    ],
    function (Component,
              ko
    ) {
      'use strict';

      const config = window.checkoutConfig?.payment?.payuApplePay ?? {};

      return Component.extend(
          {
            defaults: {
              template: 'PayU_PaymentGateway/payment/payu_gateway_apple_pay',
              postPlaceOrderData: 'payu/data/getPostPlaceOrderData',
              applePaySessionUrl: 'payu/data/getApplePayMerchantSession',
              logoSrc: config.logoSrc,
              language: config.language,
              displayName: config.displayName,
              termsUrl: config.termsUrl,
              payuAgreement: ko.observable(true),
              payuMore1: ko.observable(false),
              payuMore2: ko.observable(false),
              applePayToken: ko.observable(null),
              isAvailable: ko.observable(false)
            },

            /**
             * @return {Object}
             */
            getData: function () {
              const token = this.applePayToken();

              return {
                'method': this.item.method,
                'additional_data': {
                  'payu_authorization_code': token ? btoa(token) : '',
                  'payu_browser_screenWidth': screen.width,
                  'payu_browser_javaEnabled': navigator.javaEnabled(),
                  'payu_browser_timezoneOffset': new Date().getTimezoneOffset(),
                  'payu_browser_screenHeight': screen.height,
                  'payu_browser_userAgent': navigator.userAgent,
                  'payu_browser_colorDepth': screen.colorDepth,
                  'payu_browser_language': navigator.language
                }
              };
            }
          }
      );
    }
);