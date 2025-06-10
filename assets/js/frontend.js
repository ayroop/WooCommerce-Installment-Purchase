(function($) {
    'use strict';

    const InstallmentPurchase = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('input[name="payment_method"]').on('change', this.handlePaymentMethodChange);
            $('#installment-application-form').on('submit', this.handleApplicationSubmit);
        },

        handlePaymentMethodChange: function() {
            const selectedMethod = $('input[name="payment_method"]:checked').val();
            const $form = $('form.cart');
            
            if (selectedMethod === 'installment') {
                $form.find('button.single_add_to_cart_button').text(wcInstallmentPurchase.i18n.processing);
            } else {
                $form.find('button.single_add_to_cart_button').text($form.find('button.single_add_to_cart_button').data('original-text'));
            }
        },

        handleApplicationSubmit: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');
            const originalButtonText = $submitButton.text();

            // Validate form
            if (!InstallmentPurchase.validateForm($form)) {
                return;
            }

            // Disable submit button and show loading state
            $submitButton.prop('disabled', true).text(wcInstallmentPurchase.i18n.processing);

            // Collect form data
            const formData = new FormData($form[0]);
            formData.append('action', 'submit_installment_application');
            formData.append('security', wcInstallmentPurchase.nonce);

            // Submit form via AJAX
            $.ajax({
                url: wcInstallmentPurchase.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Redirect to payment page or show success message
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            InstallmentPurchase.showMessage('success', response.data.message);
                            $form[0].reset();
                        }
                    } else {
                        InstallmentPurchase.showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    InstallmentPurchase.showMessage('error', wcInstallmentPurchase.i18n.error);
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text(originalButtonText);
                }
            });
        },

        validateForm: function($form) {
            let isValid = true;
            const requiredFields = $form.find('[required]');

            requiredFields.each(function() {
                const $field = $(this);
                if (!$field.val()) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });

            if (!isValid) {
                InstallmentPurchase.showMessage('error', wcInstallmentPurchase.i18n.requiredFields);
            }

            return isValid;
        },

        showMessage: function(type, message) {
            const $messageContainer = $('.wc-installment-message');
            
            if (!$messageContainer.length) {
                $('<div class="wc-installment-message"></div>').insertBefore('#installment-application-form');
            }

            $('.wc-installment-message')
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .show()
                .delay(5000)
                .fadeOut();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        InstallmentPurchase.init();
    });

})(jQuery); 