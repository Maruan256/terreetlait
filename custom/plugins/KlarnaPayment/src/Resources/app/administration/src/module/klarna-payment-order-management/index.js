import './extension/sw-order-detail';
import './extension/sw-order-detail-base';

import './page/klarna-payment-tab';

import './component/klarna-capture-button';
import './component/klarna-order-items';
import './component/klarna-payment-authorization';
import './component/klarna-payment-cancel';
import './component/klarna-refund-button';
import './component/klarna-release-amount';
import './component/klarna-transaction-history';

import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

const { Module } = Shopware;

Module.register('klarna-payment-order-management', {
    type: 'plugin',
    name: 'KlarnaPayment',
    title: 'klarna-payment-order-management.general.title',
    description: 'klarna-payment-order-management.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                component: 'klarna-payment-tab',
                name: 'klarna-payment-order-management.payment.detail',
                isChildren: true,
                meta: {
                    parentPath: 'sw.order.index'
                },
                path: '/sw/order/detail/:id/klarna/:transaction'
            });
        }

        next(currentRoute);
    }
});
