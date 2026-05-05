define(['jquery'], function ($) {
    'use strict';

    function resolveAmount(data) {
        if (!data) {
            return null;
        }

        if (data.finalPrice && typeof data.finalPrice.amount === 'number') {
            return data.finalPrice.amount;
        }

        if (typeof data.finalPrice === 'number') {
            return data.finalPrice;
        }

        return null;
    }

    return function (config) {
        function onWidgetLoaded(result) {
            const widgetEl = document.querySelector(config.selector);
            if (widgetEl) {
                widgetEl.style.display = result.isWidgetAvailable ? 'block' : 'none';
            }
        }

        function onWidgetError(e) {
            console.error(e && e.toString ? e.toString() : e);
        }

        function renderWidget(amount) {
            if (!amount) {
                return;
            }

            if (!window.OpenPayU || !window.OpenPayU.Installments || !window.OpenPayU.Installments.miniInstallment) {
                return;
            }

            window.OpenPayU.Installments.miniInstallment(config.selector, {
                creditAmount: amount,
                currencySign: config.currencySign,
                lang: config.lang,
                posId: config.posId,
                key: config.key,
                showLongDescription: config.showLongDescription,
                excludedPaytypes: config.excludedPaytypes || []
            })
                .then(onWidgetLoaded)
                .catch(onWidgetError);
        }

        function init() {
            renderWidget(config.creditAmount);

            if (!config.productId) {
                return;
            }

            $(document).on('priceUpdated', (event, data) => {
                const productId = event.target.dataset.productId;
                if (config.productId !== productId) {
                    return;
                }
                const amount = resolveAmount(data);
                renderWidget(amount)
            });
        }

        init();
    };
});