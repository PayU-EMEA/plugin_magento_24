/*browser:true*/
/*global define*/
var payuSDK = window.payuConfig.env === 'sandbox' ? 'payuSDKSandbox' : 'payuSDK';

define(
    [
        'jquery',
        'repayExtended',
        'mage/url',
        'ko',
        'mage/translate',
        'repay',
        payuSDK
    ],
    function (
        $,
        Component,
        url,
        ko,
        $t,
        repayModel
    ) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'PayU_PaymentGateway/order/payu_gateway_card',
                    payuMethod: ko.observable(null),
                    isChecked: repayModel.method,
                    cardToken: ko.observable(null),
                    payuAgreement: ko.observable(true),
                    payuStoreCard: ko.observable(true),
                    payuMore1: ko.observable(false),
                    payuMore2: ko.observable(false),
                    useNewCard: ko.observable(false),
                    storedActiveStatus: 'ACTIVE',
                    secureFormError: ko.observable(''),
                    secureFormOptions: {
                        elementFormNumber: '#payu-card-number',
                        elementFormDate: '#payu-card-date',
                        elementFormCvv: '#payu-card-cvv',
                        config: {
                            cardIcon: true,
                            placeholder: {
                                number: '',
                                cvv: ''
                            },
                            style: {
                                basic: {
                                    fontSize: '18px',
                                }
                            },
                            lang: 'en'
                        }
                    }
                },
                /**
                 * @returns {exports.initialize}
                 */
                initialize: function () {
                    this._super();

                    this.secureFormOptions.config.lang = this.language;

                    if (!this.storedCardsExist()) {
                        this.useNewCard(true);
                    }
                    try {
                        this.payuSDK = PayU(this.secureForm['posId'], true);
                    } catch (e) {
                        this.payuSDK = null
                        console.log(e)
                    }

                    return this;
                },

                renderSecureForm: function () {
                    if (this.payuSDK) {
                        this.secureForms = this.payuSDK.secureForms();
                        this.secureFormNumber = this.secureForms.add('number', this.secureFormOptions.config);
                        this.secureFormNumber.render(this.secureFormOptions.elementFormNumber);
                        this.secureFormDate = this.secureForms.add('date', this.secureFormOptions.config);
                        this.secureFormDate.render(this.secureFormOptions.elementFormDate);
                        this.secureFormCvv = this.secureForms.add('cvv', this.secureFormOptions.config);
                        this.secureFormCvv.render(this.secureFormOptions.elementFormCvv);
                    }
                },

                clearSecureForm: function () {
                    this.secureFormNumber.clear();
                    this.secureFormDate.clear();
                    this.secureFormCvv.clear();
                },

                /**
                 * @return {Object}
                 */
                getData: function () {
                    return {
                        'method': this.getCode(),
                        'payu_method': this.cardToken(),
                        'payu_method_type': this.transferKey,
                        'order_id': this.orderId,
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
                }
            }
        );
    }
);
