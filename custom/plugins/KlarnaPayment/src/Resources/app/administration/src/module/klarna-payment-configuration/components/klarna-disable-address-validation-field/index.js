import template from './klarna-disable-address-validation-field.html.twig';

const { Component } = Shopware;

Component.register('klarna-disable-address-validation-field', {
    template,

    compatConfig: Shopware.compatConfig,

    emits: ['update:value'],

    props: {
        value: {
            type: Boolean,
            required: false,
        },

        checked: {
            type: Boolean,
            required: false,
        },
    },

    computed: {
        checkedValue() {
            return this.value || this.checked;
        },

        useMeteorComponent() {
            // Use new meteor component in major
            if (Shopware.Feature.isActive('v6.7.0.0')) {
                return true;
            }

            // Throw warning when deprecated component is used
            Shopware.Utils.debug.warn(
                'sw-switch-field',
                // eslint-disable-next-line max-len
                'The old usage of "sw-switch-field" is deprecated and will be removed in v6.7.0.0. Please use "mt-switch" instead.',
            );

            return false;
        },
    },

    methods: {
        onChangeHandler(value) {
            if(value){
                this.showDisableAddressValidationModal = true;

                setTimeout(() => {
                    /** set link on modal text */
                    let modalText = document.querySelector('.klarna-disable-address-validation-modal .sw-confirm-modal__text');
                    modalText.innerHTML = modalText.innerHTML + " <a href='https://www.klarna.com/international/merchant-protection-program/' target='_blank'>Merchant Protection Program</a>.";
                }, 100);
            }

            this.$emit('update:value', value);
        },

        onCloseDisableAddressValidationModal(){
            this.showDisableAddressValidationModal = false;

            this.$emit('update:value', false);
        },

        onConfirmDisableAddressValidation(){
            this.$emit('update:value', true);
            
            this.showDisableAddressValidationModal = false;
        }
    },

    data() {
        return {
            showDisableAddressValidationModal: false
        };
    },
});