import './components/klarna-payment-plugin-icon';
import './components/klarna-select-order-state';
import './components/klarna-select-delivery-state';
import './components/klarna-select-salutation';
import './components/klarna-disable-address-validation-field';
import './components/klarna-disable-line-item-validation-field';

import './page/klarna-payment-settings';

import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

const { Module } = Shopware;

let configuration = {
    type: 'plugin',
    name: 'KlarnaPayment',
    title: 'klarna-payment-configuration.module.title',
    description: 'klarna-payment-configuration.module.description',
    version: '1.0.0',
    targetVersion: '1.0.0',
    
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        settings: {
            component: 'klarna-payment-settings',
            path: 'settings',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },
    
    settingsItem: {
        name:   'klarna-payment-configuration',
        to:     'klarna.payment.configuration.settings',
        label:  'klarna-payment-configuration.module.title',
        group:  'plugins',
        iconComponent: 'klarna-payment-plugin-icon',
    }
};

Module.register('klarna-payment-configuration', configuration);