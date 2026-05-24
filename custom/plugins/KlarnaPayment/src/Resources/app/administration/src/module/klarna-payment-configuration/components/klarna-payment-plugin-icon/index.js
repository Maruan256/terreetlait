import template from './klarna-payment-plugin-icon.html.twig';

const { Component, Filter } = Shopware;

Component.register('klarna-payment-plugin-icon', {
    template,

    computed: {
        assetFilter() {
            return Filter.getByName('asset');
        },
    }
});
