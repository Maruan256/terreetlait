const { Component } = Shopware;

Component.override('sw-form-field-renderer', {
    computed: {
        optionTranslations() {
            if (['klarna-select-salutation', 'klarna-select-delivery-state', 'klarna-select-order-state'].includes(this.componentName)) {
                if (!this.config.hasOwnProperty('options')) {
                    return {};
                }

                const options = [];
                let labelProperty = 'label';

                // Use custom label property if defined
                if (this.config.hasOwnProperty('labelProperty')) {
                    labelProperty = this.config.labelProperty;
                }

                this.config.options.forEach(option => {
                    const translation = this.getTranslations(
                        'options',
                        option,
                        [labelProperty],
                    );
                    // Merge original option with translation
                    const translatedOption = { ...option, ...translation };
                    options.push(translatedOption);
                });

                return { options };
            }

            return this.$super('optionTranslations');
        },
    }
});

