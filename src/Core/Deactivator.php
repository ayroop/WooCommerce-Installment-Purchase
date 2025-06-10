<?php

namespace WooCommerce\InstallmentPurchase\Core;

class Deactivator {
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wc_installment_check_due_payments');
        
        // Note: We don't delete tables or options on deactivation
        // This allows users to reactivate the plugin without losing data
    }
} 