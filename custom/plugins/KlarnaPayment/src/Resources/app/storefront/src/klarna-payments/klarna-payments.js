/* eslint-disable import/no-unresolved */
/* global Klarna */

import Plugin                      from 'src/plugin-system/plugin.class';
import PageLoadingIndicatorUtil    from 'src/utility/loading-indicator/page-loading-indicator.util';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import DomAccess                   from 'src/helper/dom-access.helper';
import HttpClient                  from 'src/service/http-client.service';
import waitForCallback             from "./callbackHelper";

export default class KlarnaPayments extends Plugin {
	/**
	 * default plugin options
	 *
	 * @type {*}
	 */
	static options = {
		url: 'https://x.klarnacdn.net/kp/lib/v1/api.js',
		saveFormDataUrl: '',
		clientToken: '',
		paymentCategory: '',
		customerData: '',
		useAuthorizationCallback: false,
		isKlarnaExpress: false,
		sessionDataUrl: '',
		authorizationCompletedUrl: ''
	};
	
	init() {
		if(!this.el) {
			return;
		}
		
		this._showElement('klarnaConfirmFormSubmit');
		
		if(this.options.paymentCategory) {
			this._disableSubmitButton();
		}
		
		this._createScript();
	}
	
	_createScript() {
		const script = document.createElement('script');
		script.type = 'text/javascript';
		script.src = this.options.url;
		
		script.addEventListener('load', this._handleScriptLoaded.bind(this), false);
		
		document.head.appendChild(script);
	}
	
	_handleScriptLoaded() {
		try {
			Klarna.Payments.init({
				client_token: this.options.clientToken
			});
		} catch (e) {
			this._hideElement('klarnaPaymentsContainer');
			this._showElement('klarnaUnavailableError');
			
			if(this.options.paymentCategory) {
				this._disableSubmitButton();
			}
			
			return;
		}
		
		const me = this;
		
		if(this.options.paymentCategory) {
			const klarnaPayment = DomAccess.querySelector(document, '.klarna-payment');
			ElementLoadingIndicatorUtil.create(klarnaPayment);
			
			me._hideElement('klarnaPaymentsContainer');
			me._emptyElement('klarnaPaymentsContainer');
			
			me._disableSubmitButton();
			
			try {
				Klarna.Payments.load({
					container: '#klarnaPaymentsContainer',
					payment_method_category: this.options.paymentCategory
				}, (result) => {
					if(!result.show_form) {
						me._hideElement('klarnaPaymentsContainer');
						me._showElement('klarnaUnavailableError');
					} else {
						me._showElement('klarnaPaymentsContainer');
						me._hideElement('klarnaUnavailableError');
						
						me._enableSubmitButton();
					}
					ElementLoadingIndicatorUtil.remove(klarnaPayment);
				});
			} catch (e) {
				console.error(e);
				me._hideElement('klarnaPaymentsContainer');
				me._showElement('klarnaUnavailableError');
			}
		}
		
		this._handlePaymentMethodModal();
		this._registerEvents();
	}
	
	_registerEvents() {
		const me = this;
		
		this._getFormSubmitButton()
		.addEventListener('click', me._handleOrderSubmit.bind(this));
		
		const inputFields = document.querySelectorAll('[name=\'paymentMethodId\']');
		
		Array.prototype.forEach.call(inputFields, (radio) => {
			radio.addEventListener('change', me._handlePaymentMethodChange.bind(me));
		});
	}
	
	_handlePaymentMethodChange(event) {
		this._hideElements('klarnaPaymentsContainerModal');
		
		const code = this.getKlarnaCodeFromPaymentMethod(event.target.value);
		
		if(!code) {
			return;
		}
		
		this._showElement(`klarnaPaymentsContainerModal${event.target.value}`);
	}
	
	_handlePaymentMethodModal() {
		const me = this;
		const paymentMethods = document.querySelectorAll('.klarna-payment-method');
		
		Array.prototype.forEach.call(paymentMethods, (paymentMethod) => {
			const id = paymentMethod.getAttribute('id');
			const code = me.getKlarnaCodeFromPaymentMethod(id);
			
			try {
				Klarna.Payments.load({
					container: `#klarnaPaymentsContainerModal${id}`,
					payment_method_category: code,
					instance_id: id
				}, (res) => {
					if(!res.show_form) {
						me._hideElement(id);
					}
				});
			} catch (e) {
				me._hideElement(id);
			}
		});
	}
	
	_hideElements(classname) {
		const elements = document.getElementsByClassName(classname);
		
		Array.prototype.forEach.call(elements, (element) => {
			element.hidden = true;
		});
	}
	
	_hideElement(element) {
		const container = document.getElementById(element);
		
		if(container) {
			container.hidden = true;
		}
	}
	
	_showElement(element) {
		const container = document.getElementById(element);
		
		if(container) {
			container.hidden = false;
		}
	}
	
	_emptyElement(element) {
		const container = document.getElementById(element);
		
		if(container) {
			container.innerHTML = '';
		}
	}
	
	getKlarnaCodeFromPaymentMethod(paymentMethod) {
		const code = document.getElementById(paymentMethod);
		
		if(code) {
			return code.getAttribute('data-klarna-code');
		}
		
		return '';
	}
	
	_disableSubmitButton() {
		const button = this._getFormSubmitButton();
		
		if(button) {
			button.setAttribute('disabled', 'disabled');
		}
	}
	
	_enableSubmitButton() {
		const button = this._getFormSubmitButton();
		
		if(button) {
			button.removeAttribute('disabled');
		}
	}
	
	_moveKlarnaModalContainer(target) {
		const container = document.getElementById('klarnaModalContainer');
		
		target.parentElement.appendChild(container);
	}
	
	_handleOrderSubmit(event) {
		const form = event.target.form;
		
		if(form && form.checkValidity() === false) {
			return;
		}
		
		if(!this.options.paymentCategory && !this.options.isKlarnaExpress) {
			return;
		}
		
		if(this.authorization) {
			return;
		}
		
		event.preventDefault();
		
		PageLoadingIndicatorUtil.create();
		
		if(this.options.isKlarnaExpress) {
			this._finalize();
			
			return;
		}
		
		if(this.options.useAuthorizationCallback) {
			this._saveFormData();
			
			return;
		}
		
		this._createAuthorization();
	}
	
	_createAuthorization() {
		const me = this;
		let isAuthorizing = true;
		
		try {
			Klarna.Payments.on('fullscreenOverlayHidden', () => {
				if(!isAuthorizing) {
					this._enableSubmitButton();
				}
				
				Klarna.Payments.off('fullscreenOverlayHidden');
			});
			
			Klarna.Payments.authorize(
				{
					auto_finalize: true,
					payment_method_category: this.options.paymentCategory
				},
				me.options.customerData,
				(result) => {
					isAuthorizing = false;
					
					if(!result.show_form) {
						me._hideElement('klarnaPaymentsContainer');
						me._showElement('klarnaUnavailableError');
					}
					
					if(result.approved) {
						Klarna.Payments.off('fullscreenOverlayHidden');
						
						me._saveAuthorization(result);
						
						if(me.options.useAuthorizationCallback) {
							waitForCallback(me.options.authorizationCompletedUrl, me.authorization).then(() => {
								me._submitConfirmForm();
							}).catch(() => {
								// Submit anyway, the controller will throw an error
								me._submitConfirmForm();
							});
							
							return;
						}
						
						me._submitConfirmForm();
					} else {
						PageLoadingIndicatorUtil.remove();
					}
				}
			);
		} catch (e) {
			me._hideElement('klarnaPaymentsContainer');
			me._showElement('klarnaUnavailableError');
		}
	}
	
	_finalize() {
		const me = this;
		const client = new HttpClient();
		
		client.get(
			this.options.sessionDataUrl,
			(sessionResponse) => {
				const sessionData = JSON.parse(sessionResponse);
				
				Klarna.Payments.finalize(
					{},
					sessionData,
					(result) => {
						if(!result.show_form) {
							me._showElement('klarnaUnavailableError');
						}
						
						if(result.approved) {
							me._saveAuthorization(result);
							me._submitConfirmForm();
						} else {
							PageLoadingIndicatorUtil.remove();
						}
					}
				);
			}
		);
	}
	
	_saveFormData() {
		const me = this;
		const client = new HttpClient();
		
		client.post(
			me.options.saveFormDataUrl,
			new FormData(document.getElementById('confirmOrderForm')),
			(response) => {
				this._createAuthorization();
			}
		);
	}
	
	_saveAuthorization(result) {
		this.authorization = result.authorization_token;
		
		this._addAuthorizationToForm(this.authorization);
	}
	
	_addAuthorizationToForm(authorization) {
		const element = document.getElementById('klarnaAuthorizationToken');
		
		if(element) {
			element.value = authorization;
		}
	}
	
	_submitConfirmForm() {
		const form = document.getElementById('confirmOrderForm');
		const formSubmitBtn = this._getFormSubmitButton();
		
		if(form && formSubmitBtn) {
			if(this._isInternetExplorer()) {
				//special handling for internet explorer which does not support form inputs outside of the actual form element
				//Bugreport: https://issues.shopware.com/issues/NEXT-14745
				const outsideElements = document.querySelectorAll('[form=\'confirmOrderForm\']');
				for(let i = 0; i < outsideElements.length; ++i) {
					form.appendChild(outsideElements[ i ]);
				}
			}
			
			formSubmitBtn.disabled = false;
			formSubmitBtn.click();
		}
	}
	
	_isInternetExplorer() {
		return window.document.documentMode !== undefined;
	}
	
	_getFormSubmitButton() {
		let formSubmitBtn = document.getElementById('confirmFormSubmit');
		
		if(!formSubmitBtn) {
			formSubmitBtn = document.querySelector('#confirmOrderForm button[type="submit"]');
		}
		
		return formSubmitBtn;
	}
}
