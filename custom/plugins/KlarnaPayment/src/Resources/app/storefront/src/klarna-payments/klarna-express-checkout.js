/* eslint-disable import/no-unresolved */
/* eslint-disable no-debugger */
/* eslint-disable no-unused-vars */
/* global Klarna */

import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';

export default class KlarnaExpressCheckout extends Plugin {
    /**
     * default plugin options
     *
     * @type {*}
     */
    static options = {
        url: 'https://x.klarnacdn.net/kp/lib/v1/api.js',
        clientKey: '',
        clientInstanceName: 'klarnaExpressCheckout',
        containerSelector: '.klarna-express-checkout-button',
        errorSelector: '.klarna-express-checkout-error',
        theme: 'default',
        shape: 'default',
        addLineItem: false,
        sessionDataUrl: '',
        loginUrl: '',
    };

    /**
     * session data from CreateSessionRequest
     * @type {Object}
     * @private
     */
    _sessionData;

    init() {
        if (!this.el) {
            return;
        }

        this._disableButton();

        this._initClient();
        this._defineKlarnaAsyncCallback();
        this._createScript();
    }

    _initClient() {
        this._client = new HttpClient();
    }

    _defineKlarnaAsyncCallback() {
        const me = this;

        window.klarnaAsyncCallback = function () {
            Klarna.Payments.Buttons.init({
                client_id: me.options.clientKey,
            }).load(
                {
                    container: me.options.containerSelector,
                    theme: me.options.theme,
                    shape: me.options.shape,
                    on_click: me._onClickKlarnaExpressCheckoutButton.bind(me)
                }
            );
        }
    }

    _createScript() {
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = this.options.url;
        script.setAttribute('data-client-instance-name', this.options.clientInstanceName);
        script.async = true;

        document.head.appendChild(script);
    }

    /**
     * @param {function} authorize
     * */
    _onClickKlarnaExpressCheckoutButton(authorize) {
        // save Klarna's authorize function for later use
        this._authorize = authorize;

        PageLoadingIndicatorUtil.create();

        this._defineKlarnaAsyncCallback();

        if (this.options.addLineItem) {
            this._addLineItem();
        } else {
            this._getSessionDataAndCreateOrderFromAuthorization();
        }

        PageLoadingIndicatorUtil.remove();
    }

    _addLineItem() {
        const me = this;
        let addToCartPluginInstance = null;

        try {
            addToCartPluginInstance = window.PluginManager.getPluginInstances('AddToCart')[0];
        } catch (e) {
            // will be handled below
        }

        if (!addToCartPluginInstance) {
            me._showError(true);

            return;
        }

        const tmpOptionsRedirectTo = addToCartPluginInstance.options.redirectTo;
        const tmpOpenOffCanvasCarts = addToCartPluginInstance._openOffCanvasCarts;

        addToCartPluginInstance.options.redirectTo = null;
        addToCartPluginInstance._openOffCanvasCarts = (requestUrl, formData) => {
            me._client.post(requestUrl, formData, () => {
                me._getSessionDataAndCreateOrderFromAuthorization.bind(me)();
            });
        };

        this.options = me.options;

        addToCartPluginInstance._formSubmit(new Event('submit'));
        addToCartPluginInstance.options.redirectTo = tmpOptionsRedirectTo;
        addToCartPluginInstance._openOffCanvasCarts = tmpOpenOffCanvasCarts;
    }

    _getSessionDataAndCreateOrderFromAuthorization() {
        this._client.get(
            this.options.sessionDataUrl,
            this._authorizeAndLogin.bind(this)
        );
    }

    /**
     * @param {string} responseText
     */
    _authorizeAndLogin(responseText) {
        const sessionData = JSON.parse(responseText);
        const me = this;

        me._authorize(
            {collect_shipping_address: true, auto_finalize: false},
            sessionData,
            (result) => {
                if (result.approved === false || result.finalize_required === false) {
                    return;
                }

                me._client.post(
                    me.options.loginUrl,
                    JSON.stringify(me._getOrderRequestPayloadFromAuthorizeResponse(result)),
                    (response) => {
                        const data = JSON.parse(response);

                        if (data.success) {
                            window.location.replace(data.redirectUrl);

                            return;
                        }

                        me._showError(true);
                    }
                );
            },
        )

        return sessionData;
    }

    _getOrderRequestPayloadFromAuthorizeResponse(authorizeResponse) {
        return {
            klarnaClientToken: authorizeResponse.client_token,
            collectedShippingAddress: authorizeResponse.collected_shipping_address,
        }
    }

    _showError(yesOrNo = false) {
        const buttonElement = DomAccess.querySelector(this.el, this.options.containerSelector),
            errorElement = DomAccess.querySelector(this.el, this.options.errorSelector);

        buttonElement.hidden = yesOrNo;
        errorElement.hidden = !yesOrNo;
    }

    _disableButton() {
        const cartButton = document.querySelector('#productDetailPageBuyProductForm button.btn-buy');

        if(cartButton){
            const buttonElement = DomAccess.querySelector(this.el, this.options.containerSelector);

            if(cartButton.disabled){
                this.el.classList.add('is-disabled');
                buttonElement.classList.add('is-disabled');
            } else {
                this.el.classList.remove('is-disabled');
                buttonElement.classList.remove('is-disabled');
            }
            
        }
    }
}
