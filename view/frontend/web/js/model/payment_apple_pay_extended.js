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

		const APPLE_PAY_SUCCESS = window.ApplePaySession ? window.ApplePaySession.STATUS_SUCCESS : 1;
		const APPLE_PAY_FAILURE = window.ApplePaySession ? window.ApplePaySession.STATUS_FAILURE : 2;

		return Component.extend({
			/**
			 * @return {exports}
			 */
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
				const APPLE_PAY_API_MAX_VERSION = 14;
				const APPLE_PAY_API_MIN_VERSION = 1;

				for (let i = APPLE_PAY_API_MAX_VERSION; i > APPLE_PAY_API_MIN_VERSION; i--) {
					if (window.ApplePaySession.supportsVersion(i)) {
						return i;
					}
				}

				return APPLE_PAY_API_MIN_VERSION;
			},

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

				try {
					this.applePaySession = new window.ApplePaySession(
						this.getApplePayApiVersion(),
						this.getPaymentRequest()
					);
				} catch (e) {
					console.error('Cannot initialize Apple Pay session:', e);
					this.isPlaceOrderActionAllowed(true);
					fullScreenLoader.stopLoader();

					return;
				}

				this.applePaySession.onvalidatemerchant = function () {
					$.getJSON(url.build(self.applePaySessionUrl))
						.done(function (response) {
							try {
								self.applePaySession.completeMerchantValidation(response);
							} catch (error) {
								console.error('Apple Pay merchant validation failed:', error);
								self.applePaySession.abort();
								self.isPlaceOrderActionAllowed(true);
								fullScreenLoader.stopLoader();
							}
						})
						.fail(function (error) {
							console.error('Apple Pay session request failed:', error);
							self.applePaySession.abort();
							self.isPlaceOrderActionAllowed(true);
							fullScreenLoader.stopLoader();
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
						self.isPlaceOrderActionAllowed(true);
						fullScreenLoader.stopLoader();

						return;
					}

					self.applePayToken(token);
					self.placeOrderWithToken();
				};

				this.applePaySession.oncancel = function () {
					self.isPlaceOrderActionAllowed(true);
					fullScreenLoader.stopLoader();
				};

				this.applePaySession.begin();
			},

			placeOrderWithToken: function () {
				const self = this;

				this.getPlaceOrderDeferredObject()
					.fail(function () {
						self.applePaySession.completePayment(APPLE_PAY_FAILURE);
						self.isPlaceOrderActionAllowed(true);
						fullScreenLoader.stopLoader();
					})
					.done(function (orderId) {
						self.applePaySession.completePayment(APPLE_PAY_SUCCESS);
						self.afterPlaceOrder();

						if (self.redirectAfterPlaceOrder) {
							$.getJSON(url.build(`${self.postPlaceOrderData}/id/${orderId}`), function (response) {
								if (response.success && response.redirectUri) {
									window.location.replace(response.redirectUri);
								} else {
									self.isPlaceOrderActionAllowed(true);
									fullScreenLoader.stopLoader();
								}
							});
						}
					});
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
					fullScreenLoader.startLoader();
					this.isPlaceOrderActionAllowed(false);
					this.openApplePaySheet();

					return true;
				}

				return false;
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

