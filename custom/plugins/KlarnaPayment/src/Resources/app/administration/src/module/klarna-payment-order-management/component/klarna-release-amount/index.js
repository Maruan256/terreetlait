import template from './klarna-release-amount.html.twig';
import './klarna-release-amount.scss';

const { Component, Mixin } = Shopware;

Component.register('klarna-release-amount', {
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
            isDisabled: false,
            isSuccessful: false,
            isModalShown: false
        };
    },

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['KlarnaPaymentOrderService'],

    computed: {
        buttonDisabled() {
            if (this.klarnaOrder.order_status === 'CANCELLED') {
                return true;
            }

            if (this.klarnaOrder.remaining_amount <= 0) {
                return true;
            }

            return this.klarnaOrder.captured_amount <= 0;
        },
        
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },
    },

    methods: {
        releaseRemainingAuthorization() {
            this.$emit('subComponentLoading', true);
            this.isDisabled = true;
            this.isLoading = true;

            this.KlarnaPaymentOrderService.releaseRemainingAuthorization(
                this.klarnaOrder.swOrderId,
                this.klarnaOrder.order_id,
                this.klarnaOrder.salesChannel
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('klarna-payment-order-management.release.messages.successTitle'),
                    message: this.$tc('klarna-payment-order-management.release.messages.successMessage')
                });

                this.isSuccessful = true;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('klarna-payment-order-management.release.messages.errorTitle'),
                    message: this.$tc('klarna-payment-order-management.release.messages.errorMessage')
                });

                this.isSuccessful = false;
            }).finally(() => {
                this.$emit('reload');
                this.$emit('subComponentLoading', false);

                this.isLoading = false;
                this.isModalShown = false;
                this.isDisabled = false;
            });
        },

        openModal() {
            this.isModalShown = true;
        },

        closeModal() {
            this.isModalShown = false;
        }
    }
});
