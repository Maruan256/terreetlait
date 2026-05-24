import template from './klarna-transaction-history.html.twig';
import './klarna-transaction-history.scss';

const { Component, Mixin } = Shopware;

Component.register('klarna-transaction-history', {
    template,

    props: {
        klarnaHistory: {
            type: Array,
            required: true
        },

        klarnaOrder: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            isLoading: true,
            hasError: false,
            showDetailModal: false,
            modalRequest: {},
            modalResponse: {},
            modalHttpStatus: null,
            isResponseShown: false,
            isRequestShown: false,
            stringResponse: '',
            stringRequest: '',
            translatedHistory: []
        };
    },

    watch: {
        modalRequest: {
            deep: true,
            immediate: true,
            handler() {
                if (null === this.modalRequest) {
                    return;
                }
                this.orderAmount = [
                    { type: 'total', value: this.klarnaOrder.order_amount }
                ];
                if (this.modalRequest.captured_amount) {
                    this.orderAmount.push({ type: 'captured', value: this.modalRequest.captured_amount / 100});
                }
                if (this.modalRequest.refunded_amount) {
                    this.orderAmount.push({ type: 'refunded', value: this.modalRequest.refunded_amount  / 100});
                }
            }
        },

        klarnaHistory: {
            deep: true,
            immediate: true,
            handler() {
                const translationPath = 'klarna-payment-order-management.transactionHistory.messages.';

                this.translatedHistory = [];

                this.klarnaHistory.forEach((element) => {
                    const combined = translationPath + element.message;
                    const translation = this.$tc(combined);
                    const translatedElement = Object.assign({}, element);

                    if (translation === combined) {
                        translatedElement.message = `${this.$tc(`${translationPath}Fallback`)} (${element.message})`;
                    } else {
                        translatedElement.message = translation;
                    }

                    this.translatedHistory.push(translatedElement);
                });
            }
        }
    },

    inject: ['KlarnaPaymentOrderService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    computed: {
        editorConfig() {
            return {
                readOnly: true
            };
        },

        transactionHistoryColumns() {
            return [
                {
                    property: 'status',
                    label: this.$tc('klarna-payment-order-management.transactionHistory.columns.status'),
                    rawData: true
                },
                {
                    property: 'date',
                    label: this.$tc('klarna-payment-order-management.transactionHistory.columns.date'),
                    rawData: true
                },
                {
                    property: 'message',
                    label: this.$tc('klarna-payment-order-management.transactionHistory.columns.message'),
                    rawData: true
                }
            ];
        },

        detailHistoryAmountColumns() {
            return [
                {
                    property: 'type',
                    label: this.$tc('klarna-payment-order-management.transactionHistory.modal.typeColumn'),
                    rawData: 'true'
                },
                {
                    property: 'value',
                    label: this.$tc('klarna-payment-order-management.transactionHistory.modal.valueColumn'),
                    rawData: 'true'
                }
            ];
        },

        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        }
    },

    methods: {
        openDetailModal(item) {
            const parsedResponse = JSON.parse(item.response);
            const parsedRequest = JSON.parse(item.request);

            this.showDetailModal = true;
            this.success = !item.error;
            this.detailModalTitle = `${this.$tc('klarna-payment-order-management.detailModal.title')} - ${item.message}`;
            this.modalRequest = parsedRequest;
            this.modalResponse = parsedResponse;
            this.modalHttpStatus = item.statusCode ? item.statusCode : null;

            if (Object.keys(parsedResponse).length !== 0 && parsedResponse.constructor === Object) {
                this.stringResponse = JSON.stringify(parsedResponse, null, 2).trimLeft();
            }

            if (Object.keys(parsedRequest).length !== 0 && parsedRequest.constructor === Object) {
                this.stringRequest = JSON.stringify(parsedRequest, null, 2).trimLeft();
            }
        },

        closeDetailModal() {
            this.showDetailModal = false;
            this.modalRequest = null;
            this.modalResponse = null;
            this.stringResponse = '';
            this.stringRequest = '';
            this.isRequestShown = false;
            this.isResponseShown = false;
        },

        toggleResponseVisibility() {
            this.isResponseShown = !this.isResponseShown;
            this.isRequestShown = false;
        },

        toggleRequestVisibility() {
            this.isRequestShown = !this.isRequestShown;
            this.isResponseShown = false;
        }
    }
});
