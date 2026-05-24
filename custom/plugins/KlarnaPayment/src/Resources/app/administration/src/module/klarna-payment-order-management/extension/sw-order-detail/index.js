import template from './sw-order-detail.html.twig';
import './sw-order-detail.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-order-detail', {
    template,

    data() {
        return {
            klarnaTransactions: []
        };
    },

    computed: {
        isEditable() {
            const route = 'klarna-payment-order-management.payment.detail';

            return this.klarnaTransactions.length === 0 || this.$route.name !== route;
        }
    },

    watch: {
        orderId: {
            deep: true,
            handler() {
                this.klarnaTransactions = [];

                if (!this.orderId) {
                    return;
                }

                this.loadOrderData();
            },
            immediate: true
        }
    },

    methods: {
        loadOrderData() {
            const orderRepository = this.repositoryFactory.create('order');

            const orderCriteria = new Criteria(1, 1);
            orderCriteria.addAssociation('transactions');
            orderCriteria.addAssociation('transactions.stateMachineState');

            return orderRepository.get(this.$route.params.id, Context.api, orderCriteria).then((order) => {
                this.loadKlarnaTransactions(order);
            });
        },

        loadKlarnaTransactions(order) {
            order.transactions.forEach((orderTransaction) => {
                if (!orderTransaction.customFields) {
                    return;
                }

                if (!orderTransaction.customFields.klarna_order_id) {
                    return;
                }

                this.klarnaTransactions.push({
                    transaction: orderTransaction.id,
                    cancelled: orderTransaction.stateMachineState.technicalName === 'cancelled'
                });
            });
        }
    }
});
