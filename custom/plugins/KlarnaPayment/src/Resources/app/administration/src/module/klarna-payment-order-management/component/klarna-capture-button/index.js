import template from './klarna-capture-button.html.twig';
import './klarna-capture-button.scss';

const { Component, Mixin } = Shopware;

Component.register('klarna-capture-button', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['KlarnaPaymentOrderService'],

    props: {
        klarnaOrder: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            isLoading: false,
            hasError: false,
            showCaptureModal: false,
            isCaptureSuccessful: false,
            selection: [],
            captureAmount: 0.0,
            description: ''
        };
    },

    computed: {
        buttonEnabled() {
            if (this.klarnaOrder.fraud_status !== 'ACCEPTED') {
                return false;
            }
            if (this.klarnaOrder.order_status === 'CANCELLED') {
                return false;
            }
            if (this.klarnaOrder.remaining_amount <= 0) {
                return false;
            }

            return true;
        },

        maxCaptureAmount() {
            return this.klarnaOrder.remaining_amount;
        },

        minCaptureValue() {
            return 0.01;
        },

        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },
    },

    methods: {
        openCaptureModal() {
            this.showCaptureModal = true;
            this.isCaptureSuccessful = false;

            this.captureAmount = this.klarnaOrder.remaining_amount;
            this.description = '';
            this.selection = [];
        },

        calculateCaptureAmount() {
            let amount = 0;

            this.selection.forEach((selection) => {
                if (selection.selected) {
                    amount += (selection.unit_price / 100) * selection.quantity;
                }
            });

            if (amount === 0 || amount > this.klarnaOrder.remaining_amount) {
                amount = this.klarnaOrder.remaining_amount;
            }

            amount = +amount;
            amount = Number(amount.toFixed(2));

            this.captureAmount = amount;
        },

        closeCaptureModal() {
            this.showCaptureModal = false;
        },

        onCaptureFinished() {
            this.isCaptureSuccessful = false;
        },

        captureOrder() {
            this.isLoading = true;

            const orderLines = [];

            this.selection.forEach((selection) => {
                this.klarnaOrder.order_lines.forEach((orderItem) => {
                    if (orderItem.reference === selection.reference && selection.selected && selection.quantity) {
                        const copy = { ...orderItem };

                        copy.quantity = selection.quantity;
                        copy.total_amount = copy.unit_price * copy.quantity;

                        const taxRate = copy.tax_rate / 100;

                        copy.total_tax_amount = Math.round(copy.total_amount / (100 + taxRate) * taxRate);

                        orderLines.push(copy);
                    }
                });
            });

            const request = {
                orderTransactionId: this.klarnaOrder.orderTransactionId,
                order_id: this.klarnaOrder.swOrderId,
                klarna_order_id: this.klarnaOrder.order_id,
                salesChannel: this.klarnaOrder.salesChannel,
                captureAmount: this.captureAmount,
                description: this.description,
                orderLines: JSON.stringify(orderLines),
                complete: this.captureAmount === this.maxCaptureAmount
            };

            this.KlarnaPaymentOrderService.captureOrder(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('klarna-payment-order-management.messages.captureSuccessTitle'),
                    message: this.$tc('klarna-payment-order-management.messages.captureSuccessMessage')
                });

                this.isCaptureSuccessful = true;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('klarna-payment-order-management.messages.captureErrorTitle'),
                    message: this.$tc('klarna-payment-order-management.messages.captureErrorMessage')
                });

                this.isCaptureSuccessful = false;
            }).finally(() => {
                this.isLoading = false;
                this.showCaptureModal = false;

                this.$emit('reload');
            });
        },

        onSelectItem(reference, selected) {
            if (this.selection.length === 0) {
                this._populateSelectionProperty();
            }

            this.selection.forEach((selection) => {
                if (selection.reference === reference) {
                    selection.selected = selected;
                }
            });

            this.calculateCaptureAmount();
        },

        onChangeQuantity(reference, quantity) {
            if (this.selection.length === 0) {
                this._populateSelectionProperty();
            }

            this.selection.forEach((selection) => {
                if (selection.reference === reference) {
                    selection.quantity = quantity;
                }
            });

            this.calculateCaptureAmount();
        },

        onChangeDescription(description) {
            const maxChars = 255;

            if (description.length >= maxChars) {
                this.description = description.substr(0, maxChars);
            } else {
                this.description = description;
            }
        },

        _populateSelectionProperty() {
            this.klarnaOrder.order_lines.forEach((orderItem) => {
                this.selection.push({
                    quantity: orderItem.quantity - orderItem.captured_quantity,
                    reference: orderItem.reference,
                    unit_price: orderItem.unit_price,
                    selected: false
                });
            });
        }
    }
});
