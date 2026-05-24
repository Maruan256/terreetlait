import template from './sw-settings-payment-detail.html.twig';

Shopware.Component.override('sw-settings-payment-detail', {
	template, inject: [
		'KlarnaPaymentConfigurationService'
	], data() {
		return { isKlarnaMethod: false };
	}, computed: {
		paymentMethodUrl() {
			return this.$router.resolve({
				name: 'sw.settings.payment.detail',
				params: { id: "2eb76b63b549a0de4fae2d0677c09062" }
			})?.href ?? "#";
		}
	}, async created() {
		this.isKlarnaMethod = (await this.KlarnaPaymentConfigurationService.getDeprecatedIds())?.ids.includes(this.paymentMethod.id);
	}
});