const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class KlarnaPaymentOrderService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'klarna_payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    fetchOrderData(orderId, klarnaOrderId, salesChannel) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/fetch_order`,
                {
                    order_id: orderId,
                    klarna_order_id: klarnaOrderId,
                    salesChannel: salesChannel
                },
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    captureOrder(request) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/capture_order`,
                request,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    refundOrder(request) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/refund_order`,
                request,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    cancelPayment(orderTransactionId, orderId, klarnaOrderId, salesChannel) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/cancel_payment`,
                {
                    orderTransactionId: orderTransactionId,
                    order_id: orderId,
                    klarna_order_id: klarnaOrderId,
                    salesChannel: salesChannel
                },
                {
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    releaseRemainingAuthorization(orderId, klarnaOrderId, salesChannel) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/release_remaining_authorization`,
                {
                    order_id: orderId,
                    klarna_order_id: klarnaOrderId,
                    salesChannel: salesChannel
                },
                {
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    extendAuthorization(orderId, klarnaOrderId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/extend_authorization`,
                {
                    order_id: orderId,
                    klarna_order_id: klarnaOrderId
                },
                {
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('KlarnaPaymentOrderService', (container) => {
    const initContainer = Application.getContainer('init');

    return new KlarnaPaymentOrderService(initContainer.httpClient, container.loginService);
});
