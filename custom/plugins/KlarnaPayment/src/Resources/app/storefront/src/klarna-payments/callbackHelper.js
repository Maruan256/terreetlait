import HttpClient from 'src/service/http-client.service';

/*
 Retries based on the auth callback documentation
 https://docs.klarna.com/payments/web-payments/integrate-with-klarna-payments/other-actions/authorization-callback/#additional-information
 */
export default (url, authorizationToken) => {
	return new Promise((resolve, reject) => {
		const client = new HttpClient();
		const maxAttempts = 4;
		const delay = 2000;
		let attempts = 0;
		
		function poll() {
			attempts++;
			
			client.post(url, JSON.stringify({ token: authorizationToken }), (result) => {
				try {
					const response = JSON.parse(result);
					
					if(response.isAuthorized) {
						return resolve();
					}
				} catch (_) {
					console.error("The JSON response could not be parsed.", result);
					
					reject();
				}
				
				if(attempts <= maxAttempts) {
					setTimeout(poll, delay);
				} else {
					reject();
				}
			});
		}
		
		poll();
	});
};
