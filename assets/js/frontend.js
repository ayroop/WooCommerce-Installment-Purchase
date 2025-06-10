(function($) {
    'use strict';

    const InstallmentPurchase = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('input[name="payment_method"]').on('change', this.handlePaymentMethodChange);
            $('#installment-application').on('submit', this.handleApplicationSubmit);
        },

        handlePaymentMethodChange: function() {
            const method = $('input[name="payment_method"]:checked').val();
            if (method === 'installment') {
                $('.installment-details').show();
                $('.installment-application-form').show();
            } else {
                $('.installment-details').hide();
                $('.installment-application-form').hide();
            }
        },

        handleApplicationSubmit: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');
            const originalButtonText = $submitButton.text();

            $submitButton.prop('disabled', true).text(installmentPurchase.i18n.processing);

            $.ajax({
                url: installmentPurchase.ajaxurl,
                type: 'POST',
                data: {
                    action: 'submit_installment_application',
                    nonce: installmentPurchase.nonce,
                    first_name: $('#first_name').val(),
                    last_name: $('#last_name').val(),
                    bank_account: $('#bank_account').val(),
                    terms: $('input[name="terms"]').is(':checked')
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert(response.data.message || installmentPurchase.i18n.error);
                    }
                },
                error: function() {
                    alert(installmentPurchase.i18n.error);
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text(originalButtonText);
                }
            });
        }
    };

    $(document).ready(function() {
        InstallmentPurchase.init();
    });

})(jQuery); 