import template from './klarna-order-items.html.twig';

const { Component, Context, Application } = Shopware;
const { Criteria } = Shopware.Data;
const { currency } = Shopware.Utils.format;

Component.register('klarna-order-items', {
    template,

    props: {
        klarnaOrder: {
            type: Object,
            required: true
        },

        mode: {
            type: String,
            required: false
        },

        returnData: {
            type: Object,
            default() {
                return {};
            },
            required: false
        },

        swagCommercialInstalled: {
            type: Boolean,
            default: false,
            required: false
        },

        isReturnDataLoading: {
            type: Boolean,
            default: false,
            required: false
        }
    },

    computed: {
        orderItems() {
            const data = [];

            this.klarnaOrder.order_lines.forEach((orderItem) => {
                const price = currency(
                    orderItem.total_amount / 100,
                    this.klarnaOrder.currency
                );

                let disabled = false;
                let quantity = orderItem.quantity;

                if (this.mode === 'refund' && orderItem.captured_quantity > 0) {
                    quantity = orderItem.captured_quantity;
                }

                if (this.mode === 'capture') {
                    quantity -= orderItem.captured_quantity;
                } else if (this.mode === 'refund') {
                    quantity -= orderItem.refunded_quantity;
                }

                if (quantity <= 0) {
                    disabled = true;
                }

                data.push({
                    id: orderItem.reference,
                    reference: orderItem.reference,
                    product: orderItem.name,
                    options: orderItem.options,
                    amount: quantity,
                    disabled: disabled,
                    price: price,
                    orderItem: orderItem,
                    returnStatus: this.returnData[orderItem.reference]
                });
            });

            return data;
        },

        orderItemColumns() {
            let columns =  [
                {
                    property: 'reference',
                    label: this.$tc('klarna-payment-order-management.modal.columns.reference'),
                    inlineEdit: 'string',
                    rawData: true
                },
                {
                    property: 'product',
                    label: this.$tc('klarna-payment-order-management.modal.columns.product'),
                    inlineEdit: 'string',
                    rawData: true
                },
                {
                    property: 'amount',
                    label: this.$tc('klarna-payment-order-management.modal.columns.amount'),
                    inlineEdit: 'number',
                    rawData: true
                },
                {
                    property: 'price',
                    label: this.$tc('klarna-payment-order-management.modal.columns.price'),
                    inlineEdit: 'number',
                    rawData: true
                }
            ];

            if (this.mode === 'refund' && this.swagCommercialInstalled) {
                columns.push({
                    property: 'returnStatus',
                    label: this.$tc('klarna-payment-order-management.modal.columns.returnStatus'),
                    rawData: true
                });
            }

            return columns;
        },

        truncateFilter() {
            return Shopware.Filter.getByName('truncate');
        },
    },

    methods: {
        onSelectItem(selection, item, selected) {
            this.$emit('select-item', item.id, selected);
        },

        onInlineEditSave(reference) {
            let newAmount = reference.amount;
            if(newAmount > reference.orderItem.quantity ) {
                newAmount = reference.orderItem.quantity;
            }
            reference.amount = newAmount;

            this.$emit('change-quantity', reference.id, reference.amount);
        }
    }
});
