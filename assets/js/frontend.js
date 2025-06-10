(function($) {
    'use strict';

    const InstallmentPurchase = {
        init: function() {
            this.bindEvents();
            // Trigger initial update if installment purchase is already selected on page load
            this.handlePaymentMethodChange(); 
        },

        bindEvents: function() {
            $(document.body).on('change', 'input[name="payment_method"]', this.handlePaymentMethodChange.bind(this));
            // Also bind to WooCommerce's update_checkout event for re-calculations
            $(document.body).on('updated_checkout', this.handlePaymentMethodChange.bind(this));

            $('#installment-application-submit-form').on('submit', this.handleApplicationSubmit.bind(this));

            // Listen for changes in the selected installment months to update total summary
            $(document.body).on('change', '#selected_installment_months', this.updateTotalSummary);
        },

        handlePaymentMethodChange: function() {
            const chosen_method = $('input[name="payment_method"]:checked').val();
            if (chosen_method === 'installment_purchase') {
                $('.installment-checkout-details').slideDown();
                $('.installment-application-form').slideDown(); // Show the application form
                this.updateInstallmentOptions();
            } else {
                $('.installment-checkout-details').slideUp();
                $('.installment-application-form').slideUp(); // Hide the application form
            }
        },

        updateInstallmentOptions: function() {
            const data = {
                action: 'wc_installment_purchase_calculate_installments',
                security: installmentPurchase.nonce
            };

            $.ajax({
                type: 'POST',
                url: installmentPurchase.ajaxurl,
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.options_html) {
                        $('#selected_installment_months').html(response.options_html);
                        // Update static text for down payment, remaining balance, service fee
                        $('.installment-checkout-details p:nth-child(2)').text(
                            installmentPurchase.i18n.downPayment + ': ' + response.down_payment_amount
                        );
                        $('.installment-checkout-details p:nth-child(3)').text(
                            installmentPurchase.i18n.remainingBalance + ': ' + response.remaining_balance
                        );
                        $('.installment-checkout-details p:nth-child(4)').text(
                            installmentPurchase.i18n.serviceFee + ': ' + response.service_fee_percentage + '%'
                        );

                        // Trigger change on select to update summary if an option was pre-selected
                        $('#selected_installment_months').trigger('change');

                    } else if (response.data && response.data.message) {
                        alert(response.data.message);
                    } else {
                        alert(installmentPurchase.i18n.error);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error: " + textStatus, errorThrown);
                    alert(installmentPurchase.i18n.error);
                }
            });
        },

        updateTotalSummary: function() {
            const selectedOption = $(this).find('option:selected');
            const monthlyText = selectedOption.text();
            if (selectedOption.val() !== '') {
                $('.installment-total-summary').text(monthlyText);
            } else {
                $('.installment-total-summary').empty();
            }
        },

        handleApplicationSubmit: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');
            const originalButtonText = $submitButton.text();

            // Basic validation
            const bankAccount = $('#bank_account').val();
            const phoneNumber = $('#phone_number').val();
            const emailAddress = $('#email_address').val();
            const termsAccepted = $('#terms_and_conditions').is(':checked');

            if (!bankAccount) {
                alert(installmentPurchase.i18n.invalidBankAccount);
                return;
            }
            if (!phoneNumber) {
                alert(installmentPurchase.i18n.invalidPhone);
                return;
            }
            if (!emailAddress || !/\S+@\S+\.\S+/.test(emailAddress)) {
                alert(installmentPurchase.i18n.invalidEmail);
                return;
            }
            if (!termsAccepted) {
                alert(installmentPurchase.i18n.acceptTerms);
                return;
            }

            $submitButton.prop('disabled', true).text(installmentPurchase.i18n.processing);

            $.ajax({
                url: installmentPurchase.ajaxurl,
                type: 'POST',
                data: {
                    action: 'submit_installment_application',
                    nonce: installmentPurchase.nonce,
                    bank_account: bankAccount,
                    phone_number: phoneNumber,
                    email_address: emailAddress,
                    terms_accepted: termsAccepted
                },
                success: function(response) {
                    if (response.success) {
                        alert('Application submitted successfully!'); // Temporary message
                        // Optionally redirect or update UI
                        // window.location.href = response.data.redirect_url;
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