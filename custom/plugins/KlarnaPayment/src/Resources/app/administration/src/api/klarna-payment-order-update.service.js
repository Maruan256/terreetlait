const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class KlarnaPaymentOrderUpdateService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'klarna_payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    updateOrder(orderId, versionId) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/update_order`,
                {
                    orderId: orderId
                },
                {
                    headers: this.getBasicHeaders(KlarnaPaymentOrderUpdateService.getVersionHeader(versionId))
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('KlarnaPaymentOrderUpdateService', (container) => {
    const initContainer = Application.getContainer('init');

    return new KlarnaPaymentOrderUpdateService(initContainer.httpClient, container.loginService);
});
