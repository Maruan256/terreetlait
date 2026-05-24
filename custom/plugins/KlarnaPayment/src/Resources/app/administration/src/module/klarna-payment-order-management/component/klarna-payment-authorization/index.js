import template from './klarna-payment-authorization.html.twig';
import './klarna-payment-authorization.scss';

const { Component, Mixin } = Shopware;

Component.register('klarna-payment-authorization', {
    template,

    props: {
        klarnaOrder: {
            type: Object,
            required: true
        }
    },

    inject: [
        'KlarnaPaymentOrderService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    computed: {
        buttonDisabled() {
            const date = Date.parse(this.klarnaOrder.expiry_date);

            if (date < new Date('now') || this.klarnaOrder.remaining_amount <= 0) {
                return true;
            }

            if (this.klarnaOrder.order_status === 'CANCELLED') {
                return true;
            }

            return false;
        },
        
        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    data() {
        return {
            showModal: false,
            isDisabled: false,
            isLoading: false,
            isSuccessful: false
        };
    },

    methods: {
        extendAuthorization() {
            this.$emit('subComponentLoading', true);

            this.isLoading = true;
            this.isDisabled = true;

            this.KlarnaPaymentOrderService.extendAuthorization(this.klarnaOrder.swOrderId, this.klarnaOrder.order_id).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('klarna-payment-order-management.messages.authorizationModal.extendSuccessTitle'),
                    message: this.$tc('klarna-payment-order-management.messages.authorizationModal.extendSuccessMessage')
                });

                this.isSuccessful = true;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('klarna-payment-order-management.messages.authorizationModal.extendErrorTitle'),
                    message: this.$tc('klarna-payment-order-management.messages.authorizationModal.extendErrorMessage')
                });

                this.isSuccessful = false;
            }).finally(() => {
                this.$emit('subComponentLoading', false);
                this.$emit('reload', true);

                this.isLoading = false;
                this.showModal = false;
                this.isDisabled = false;
            });
        },

        openModal() {
            this.showModal = true;
            this.isSuccessful = false;
        },

        closeModal() {
            this.showModal = false;
        }
    }
});
