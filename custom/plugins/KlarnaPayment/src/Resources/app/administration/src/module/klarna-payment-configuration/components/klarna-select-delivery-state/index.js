const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('klarna-select-delivery-state', 'sw-entity-single-select', {
    props: {
        criteria: {
            type: Object,
            required: false,
            default() {
                const criteria = new Criteria(1, 100);

                criteria.addFilter(
                    Criteria.equals(
                        'stateMachine.technicalName',
                        'order_delivery.state'
                    )
                );

                return criteria;
            }
        }
    }
});
