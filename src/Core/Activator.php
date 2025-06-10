<?php

namespace WooCommerce\InstallmentPurchase\Core;

class Activator {
    public static function activate() {
        self::create_tables();
        self::create_pages();
        self::set_default_options();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Applications table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}installment_applications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            bank_account varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            fee_paid tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";

        // Installment schedules table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}installment_schedules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            application_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            due_date date NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            payment_date datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY application_id (application_id),
            KEY due_date (due_date),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    private static function create_pages() {
        // Create application page
        $application_page = array(
            'post_title'    => __('Installment Application', 'installment-purchase'),
            'post_content'  => '[installment_application]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
        );

        $page_id = wp_insert_post($application_page);
        if ($page_id) {
            update_option('wc_installment_application_page_id', $page_id);
        }
    }

    private static function set_default_options() {
        $default_options = array(
            'wc_installment_min_down_payment' => 50, // 50%
            'wc_installment_service_fee' => 5, // 5%
            'wc_installment_max_months' => 6,
            'wc_installment_inquiry_fee' => 100000, // 100,000 IRR
        );

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
} 