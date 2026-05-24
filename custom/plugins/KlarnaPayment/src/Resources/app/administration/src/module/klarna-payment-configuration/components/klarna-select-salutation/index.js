const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('klarna-select-salutation', 'sw-entity-single-select', {
    props: {
        criteria: {
            type: Object,
            required: false,
            default() {
                return new Criteria(1, 100);
            }
        }
    }
});
