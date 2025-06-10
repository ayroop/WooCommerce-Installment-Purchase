<?php

namespace WooCommerce\InstallmentPurchase\Core;

class Deactivator {
    public static function deactivate() {
        // Clean up any scheduled events
        wp_clear_scheduled_hook('wc_installment_purchase_daily_cleanup');
        
        // Optionally, you can remove the database tables
        // self::remove_tables();
    }

    private static function remove_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wc_installment_applications");
    }
} 