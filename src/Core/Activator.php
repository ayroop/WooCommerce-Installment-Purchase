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

        $table_name = $wpdb->prefix . 'wc_installment_applications';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            bank_account varchar(255) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private static function create_pages() {
        $page_id = wp_insert_post(array(
            'post_title'     => __('Installment Application', 'installment-purchase'),
            'post_content'   => '[installment_application_form]',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_name'      => 'installment-application'
        ));

        if ($page_id) {
            update_option('wc_installment_application_page_id', $page_id);
        }
    }

    private static function set_default_options() {
        $defaults = array(
            'wc_installment_purchase_service_fee' => '2.5',
            'wc_installment_purchase_max_months' => '24',
            'wc_installment_purchase_inquiry_fee' => '100000'
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }
} 