const { Component, Mixin } = Shopware;

Component.override('sw-order-detail-base', {
    inject: ['KlarnaPaymentOrderUpdateService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    methods: {
        onSaveEdits() {
            this.$emit('loading-change', true);
            this.$emit('editing-change', false);

            this.KlarnaPaymentOrderUpdateService.updateOrder(this.orderId, this.versionContext.versionId).then(async () => {
                await this.$super('onSaveEdits');
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('klarna-payment-order-management.messages.updateErrorTitle'),
                    message: this.$tc('klarna-payment-order-management.messages.updateErrorMessage')
                });

                this.versionContext.versionId = Shopware.Context.api.liveVersionId;
                this.reloadEntityData();
            });
        }
    }
});
