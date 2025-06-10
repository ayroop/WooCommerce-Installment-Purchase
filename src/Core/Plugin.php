<?php

namespace WooCommerce\InstallmentPurchase\Core;

class Plugin {
    private static $instance = null;
    private $loader;
    private $admin;
    private $frontend;
    private $gateway;
    private $api;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_frontend_hooks();
        $this->define_gateway_hooks();
        $this->define_api_hooks();
    }

    private function load_dependencies() {
        // Load core classes
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Core/Loader.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Core/Activator.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Core/Deactivator.php';

        // Load other components
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Admin/Admin.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Frontend/Frontend.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Gateway/Gateway.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/API/API.php';

        $this->loader = new Loader();
        $this->admin = new \WooCommerce\InstallmentPurchase\Admin\Admin();
        $this->frontend = new \WooCommerce\InstallmentPurchase\Frontend\Frontend();
        $this->gateway = new \WooCommerce\InstallmentPurchase\Gateway\Gateway();
        $this->api = new \WooCommerce\InstallmentPurchase\API\API();
    }

    private function define_admin_hooks() {
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $this->admin, 'add_menu_pages');
        $this->loader->add_action('admin_init', $this->admin, 'register_settings');
    }

    private function define_frontend_hooks() {
        $this->loader->add_action('wp_enqueue_scripts', $this->frontend, 'enqueue_scripts');
        $this->loader->add_action('woocommerce_before_add_to_cart_button', $this->frontend, 'render_purchase_options');
        $this->loader->add_action('template_redirect', $this->frontend, 'handle_installment_selection');
        $this->loader->add_shortcode('installment_application', $this->frontend, 'render_application_form');
    }

    private function define_gateway_hooks() {
        $this->loader->add_filter('woocommerce_payment_gateways', $this->gateway, 'add_gateway');
    }

    private function define_api_hooks() {
        $this->loader->add_action('rest_api_init', $this->api, 'register_routes');
        $this->loader->add_action('wp_ajax_submit_installment_application', $this->api, 'handle_application_submission');
        $this->loader->add_action('wp_ajax_nopriv_submit_installment_application', $this->api, 'handle_application_submission');
    }

    public function run() {
        $this->loader->run();
    }
} 