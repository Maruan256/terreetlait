import template from './klarna-order-cancel.html.twig';
import './klarna-order-cancel.scss';

const { Component, Mixin } = Shopware;

Component.register('klarna-order-cancel', {
    template,

    props: {
        klarnaOrder: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            isLoading: false,
            isSuccessful: false,
            isModalShown: false
        };
    },

    computed: {
        isDisabled() {
            return !(this.klarnaOrder.captured_amount === 0 && this.klarnaOrder.order_status !== 'CANCELLED');
        }
    },

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['KlarnaPaymentOrderService'],

    methods: {
        showModal() {
            this.isModalShown = true;
            this.isSuccess = false;
        },

        closeModal() {
            this.isModalShown = false;
        },

        cancelPayment() {
            this.$emit('subComponentLoading', true);

            this.isDisabled = true;
            this.isLoading = true;

            this.KlarnaPaymentOrderService.cancelPayment(
                this.klarnaOrder.orderTransactionId,
                this.klarnaOrder.swOrderId,
                this.klarnaOrder.order_id,
                this.klarnaOrder.salesChannel
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('klarna-payment-order-management.cancellation.messages.successTitle'),
                    message: this.$tc('klarna-payment-order-management.cancellation.messages.successMessage')
                });

                this.isSuccessful = true;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('klarna-payment-order-management.cancellation.messages.failureTitle'),
                    message: this.$tc('klarna-payment-order-management.cancellation.messages.failureMessage')
                });

                this.isSuccessful = false;
            }).finally(() => {
                this.$emit('reload');
                this.$emit('subComponentLoading', false);

                this.isLoading = false;
                this.isModalShown = false;
            });
        }
    }
});
