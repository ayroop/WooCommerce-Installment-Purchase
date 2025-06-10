(function($) {
    'use strict';

    const InstallmentAdmin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('.approve-application').on('click', this.handleApprove);
            $('.decline-application').on('click', this.handleDecline);
        },

        handleApprove: function(e) {
            e.preventDefault();
            
            if (!confirm(wcInstallmentAdmin.i18n.confirmApprove)) {
                return;
            }
            
            const $button = $(this);
            const applicationId = $button.data('id');
            
            $button.prop('disabled', true).text(wcInstallmentAdmin.i18n.processing);
            
            $.ajax({
                url: wcInstallmentAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_application_status',
                    application_id: applicationId,
                    status: 'approved',
                    security: wcInstallmentAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('Approve');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text('Approve');
                }
            });
        },

        handleDecline: function(e) {
            e.preventDefault();
            
            if (!confirm(wcInstallmentAdmin.i18n.confirmDecline)) {
                return;
            }
            
            const $button = $(this);
            const applicationId = $button.data('id');
            
            $button.prop('disabled', true).text(wcInstallmentAdmin.i18n.processing);
            
            $.ajax({
                url: wcInstallmentAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_application_status',
                    application_id: applicationId,
                    status: 'declined',
                    security: wcInstallmentAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('Decline');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text('Decline');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        InstallmentAdmin.init();
    });

})(jQuery); 