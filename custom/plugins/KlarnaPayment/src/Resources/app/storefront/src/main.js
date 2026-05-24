import KlarnaPayments        from './klarna-payments/klarna-payments';
import KlarnaExpressCheckout from './klarna-payments/klarna-express-checkout';
import SignInWithKlarna      from './klarna-payments/sign-in-with-klarna.plugin';
import KlarnaPaymentSelect    from "./klarna-payments/klarna-payment-select";

const PluginManager = window.PluginManager;

PluginManager.register('KlarnaPayments', KlarnaPayments, '[data-is-klarna-payments]');
PluginManager.register('KlarnaExpressCheckout', KlarnaExpressCheckout, '[data-is-klarna-express-checkout]');
PluginManager.register('SignInWithKlarna', SignInWithKlarna, '[data-sign-in-with-klarna]');
PluginManager.register('KlarnaPaymentSelect', KlarnaPaymentSelect, '[data-klarna-select]');

if(module.hot) {
	module.hot.accept();
}
