import template from './klarna-payment-tab.html.twig';
import './klarna-payment-tab.scss';

const { Component, Mixin, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('klarna-payment-tab', {
    template,

    data() {
        return {
            identifier: '',
            initialized: false,
            isLoading: true,
            isSubComponentLoading: false,
            hasError: false,
            klarnaOrder: {},
            splitBreakpoint: 1024,
            isMobile: false,
            klarnaHistory: {},
            orderAmount: []
        };
    },

    created() {
        this.createdComponent();
    },

    destroyed() {
        this.destroyedComponent();
    },

    watch: {
        '$route'() {
            this.loadData();
        }
    },

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: [
        'KlarnaPaymentOrderService',
        'repositoryFactory'
    ],

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },
    },

    methods: {
        createdComponent() {
            Shopware.Utils.EventBus.on('language-change', this.loadData);

            this.$device.onResize({
                listener: this.checkViewport.bind(this)
            });

            this.checkViewport();
            this.loadData();
        },

        destroyedComponent() {
            Shopware.Utils.EventBus.off('language-change', this.loadData);
        },

        checkViewport() {
            this.isMobile = this.$device.getViewportWidth() < this.splitBreakpoint;
        },

        loadData() {
            if(this.$route.name !== "klarna-payment-order-management.payment.detail") {
                return;
            }
            const me = this;

            me.isLoading = true;
            me.hasError = false;

            const repository = this.repositoryFactory.create('order_transaction');

            const criteria = new Criteria(1, 1);
            criteria.addAssociation('order');
            criteria.addAssociation('order.lineItems');

            return repository.get(this.$route.params.transaction, Context.api, criteria).then((transaction) => {
                const klarnaOrderId = transaction.customFields.klarna_order_id;
                const salesChannel = transaction.order.salesChannelId;

                me.$emit('identifier-change', transaction.order.orderNumber);

                me.KlarnaPaymentOrderService.fetchOrderData(transaction.order.id, klarnaOrderId, salesChannel).then((response) => {
                    me.hasError = false;
                    me.initialized = true;

                    me.klarnaOrder = response.order;
                    me._populateKlarnaOrderWithVariantInfo(transaction.order)

                    me.klarnaOrder.salesChannel = salesChannel;
                    me.klarnaOrder.swOrderId = transaction.order.id;
                    me.klarnaOrder.orderTransactionId = this.$route.params.transaction;

                    me.klarnaHistory = response.transactionHistory;
                }).catch(() => {
                    me.createNotificationError({
                        title: me.$tc('klarna-payment-order-management.messages.loadErrorTitle'),
                        message: me.$tc('klarna-payment-order-management.messages.loadErrorMessage')
                    });

                    me.hasError = true;
                }).finally(() => {
                    me.isLoading = false;
                });
            }).catch(() => {
                me.createNotificationError({
                    title: me.$tc('klarna-payment-order-management.messages.loadErrorTitle'),
                    message: me.$tc('klarna-payment-order-management.messages.loadErrorMessage')
                });

                me.hasError = true;
                me.isLoading = false;
            });
        },

        _populateKlarnaOrderWithVariantInfo(order) {
            this.klarnaOrder.order_lines.forEach((klarnaItem) => {
                for (const lineItem of order.lineItems) {
                    if (klarnaItem.reference === lineItem.payload.productNumber) {
                        klarnaItem.options = lineItem.payload.options;
                        // break out of `forEach` callback; caps `for` iterations
                        return;
                    }
                }
            })
        },

        setSubComponentLoading(subComponentLoading) {
            this.isSubComponentLoading = subComponentLoading;
        }
    }
});
