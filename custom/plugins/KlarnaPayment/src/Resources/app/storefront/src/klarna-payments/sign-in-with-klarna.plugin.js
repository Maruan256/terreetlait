/* eslint-disable import/no-unresolved */
/* eslint-disable no-debugger */
/* eslint-disable no-unused-vars */
/* global Klarna */

import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';

export default class SignInWithKlarna extends Plugin {
    /**
     * default plugin options
     *
     * @type {*}
     */
    static options = {
        url: 'https://js.klarna.com/web-sdk/v1/klarna.js',
        clientKey: '',
        clientInstanceName: 'signInWithKlarna',
        containerSelector: '.sign-in-with-klarna-button',
        errorSelector: '.sign-in-with-klarna-error',
        theme: 'default',
        shape: 'default',
        dataKeys: '',
        local: 'en-GB',
        callbackUrl: '',
        redirectRoute: '',
        errorRoute: '',
        redirectUri: ''
    };

    init(){
        this._client = new HttpClient();

        this._defineKlarnaSDKCallback();
        this._createScript();
    }

    _defineKlarnaSDKCallback() {
        const me = this;

        window.KlarnaSDKCallback = async (Klarna) => {
            try {
                const klarnaSDK = await Klarna.init({
                    clientId: me.options.clientKey,
                    locale: me.options.local
                });

                const scopes = "openid offline_access payment:request:create profile:name profile:email profile:billing_address" + (me.options.dataKeys && " " + me.options.dataKeys);

                const siwkButton = klarnaSDK.Identity.button({
                    scope: scopes,
                    redirectUri: me.options.redirectUri,
                    theme: me.options.theme,
                    shape: me.options.shape,
                    locale: me.options.local
                });

                klarnaSDK.Identity.on("signin", async (signinResponse) => {
                    const redirectRoute = me.options.redirectRoute;
                    const errorRoute = me.options.errorRoute;

                    me._client.post(
                        me.options.callbackUrl,
                        JSON.stringify({signinResponse, redirectRoute, errorRoute}),
                        (response) => {
                            const data = JSON.parse(response);

                            if (data.success) {
                                window.location.replace(data.redirectUrl);

                                return;
                            }

                            me._showError(true);
                        }
                    );
                });

                klarnaSDK.Identity.on("error", (error) => {
                    console.log('klarna-error', error);
                })

                siwkButton.mount("#sign-in-with-klarna");
            } catch (error) {
                console.error("Fatal SIWK error", error);
                
            }
        }
    }

    _createScript() {
        const script = document.createElement('script');
        script.defer = true;
        script.src = this.options.url;
        script.setAttribute('client-id', this.options.clientKey);
        script.setAttribute('client-instance-name', this.options.clientInstanceName);

        document.head.appendChild(script);
    }

    _showError(yesOrNo = false) {
        const buttonElement = DomAccess.querySelector(this.el, this.options.containerSelector),
            errorElement = DomAccess.querySelector(this.el, this.options.errorSelector);

        buttonElement.hidden = yesOrNo;
        errorElement.hidden = !yesOrNo;
    }
}