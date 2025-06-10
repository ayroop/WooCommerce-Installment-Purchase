<?php

namespace WooCommerce\InstallmentPurchase\Core;

use WooCommerce\InstallmentPurchase\Admin\Admin;
use WooCommerce\InstallmentPurchase\Frontend\Frontend;
use WooCommerce\InstallmentPurchase\API\API;

class Plugin {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'installment-purchase';
        $this->version = WC_INSTALLMENT_PURCHASE_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_frontend_hooks();
        $this->define_api_hooks();
    }

    private function load_dependencies() {
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Core/Loader.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Core/Activator.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Core/Deactivator.php';

        $this->loader = new Loader();
    }

    private function define_admin_hooks() {
        $admin = new Admin($this->plugin_name, $this->version);

        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $admin, 'add_menu_pages');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
    }

    private function define_frontend_hooks() {
        $frontend = new Frontend($this->plugin_name, $this->version);

        $this->loader->add_action('wp_enqueue_scripts', $frontend, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $frontend, 'enqueue_scripts');
        $this->loader->add_action('woocommerce_before_add_to_cart_button', $frontend, 'render_purchase_options');
        $this->loader->add_action('woocommerce_before_cart', $frontend, 'render_application_form');
    }

    private function define_api_hooks() {
        $api = new API();

        $this->loader->add_action('wp_ajax_submit_installment_application', $api, 'handle_application_submission');
        $this->loader->add_action('wp_ajax_nopriv_submit_installment_application', $api, 'handle_application_submission');
        $this->loader->add_action('wp_ajax_approve_application', $api, 'handle_application_approval');
        $this->loader->add_action('wp_ajax_decline_application', $api, 'handle_application_decline');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
} 