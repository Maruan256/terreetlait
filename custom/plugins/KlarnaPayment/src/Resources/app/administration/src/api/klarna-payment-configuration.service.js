const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class KlarnaPaymentConfigurationService extends ApiService {
	constructor(httpClient, loginService, apiEndpoint = 'klarna_payment') {
		super(httpClient, loginService, apiEndpoint);
	}
	
	validateCredentials(credentials, endpoint) {
		const payload = { ...credentials, endpoint };
		
		return this.httpClient
		.post(
			`_action/${this.getApiBasePath()}/validate-credentials`,
			payload,
			{
				headers: this.getBasicHeaders()
			}
		)
		.then((response) => {
			return ApiService.handleResponse(response);
		});
	}
	
	getEuApiRegionKey() {
		return this.getApiRegionKey('eu');
	}
	
	getUsApiRegionKey() {
		return this.getApiRegionKey('us');
	}
	
	getApiRegionKey(region) {
		return this.httpClient
		.post(
			`_action/${this.getApiBasePath()}/fetch-${region}-api-region-key`,
			{},
			{
				headers: this.getBasicHeaders()
			}
		)
		.then((response) => {
			return ApiService.handleResponse(response);
		});
	}
	
	getDeprecatedIds() {
		return this.httpClient
		.get(
			`_action/${this.getApiBasePath()}/get-old-ids`,
			{},
			{
				headers: this.getBasicHeaders()
			}
		)
		.then((response) => {
			return ApiService.handleResponse(response);
		});
	}
}

Application.addServiceProvider('KlarnaPaymentConfigurationService', (container) => {
	const initContainer = Application.getContainer('init');
	
	return new KlarnaPaymentConfigurationService(initContainer.httpClient, container.loginService);
});

