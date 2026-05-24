const { Component } = Shopware;

Component.override('sw-system-config', {

    computed: {
        typesWithMapInheritanceSupport() {
            let types = this.$super('typesWithMapInheritanceSupport');

            if (this.domain === 'KlarnaPayment.settings') {
                types.push('single-select');
                types.push('multi-select');
                types.push('bool');
            }

            return types;
        }
    },

    methods: {
        onSalesChannelChanged(salesChannelId) {
            this.$super('onSalesChannelChanged', salesChannelId);

            this.$emit('saleschannel-changed', this.currentSalesChannelId);
        },

        hasMapInheritanceSupport(element) {
            const customComponentNames = [
                'klarna-disable-address-validation-field',
                'klarna-disable-line-item-validation-field',
                'klarna-select-salutation',
                'klarna-select-delivery-state',
                'klarna-select-order-state',
            ];

            const componentName = element.config ? element.config.componentName : undefined;

            if (customComponentNames.includes(componentName)) {
                return true;
            }

            return this.$super('hasMapInheritanceSupport', element);
        },
    }
});

