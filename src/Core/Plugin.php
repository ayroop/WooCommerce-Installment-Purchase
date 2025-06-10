<?php

namespace WooCommerce\InstallmentPurchase\Core;

class Plugin {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'installment-purchase';
        $this->version = WC_INSTALLMENT_PURCHASE_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Core/Loader.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Admin/Admin.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Frontend/Frontend.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Gateway/Gateway.php';
        require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/API/API.php';

        $this->loader = new Loader();
    }

    private function define_admin_hooks() {
        $admin = new \WooCommerce\InstallmentPurchase\Admin\Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $admin, 'add_menu_page');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
    }

    private function define_public_hooks() {
        $frontend = new \WooCommerce\InstallmentPurchase\Frontend\Frontend($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('wp_enqueue_scripts', $frontend, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $frontend, 'enqueue_scripts');
        $this->loader->add_action('woocommerce_before_add_to_cart_button', $frontend, 'render_payment_options');
        $this->loader->add_action('woocommerce_after_add_to_cart_button', $frontend, 'render_application_form');
        
        // Add installment purchase gateway
        add_filter('woocommerce_payment_gateways', function($gateways) {
            $gateways[] = 'WooCommerce\InstallmentPurchase\Gateway\Gateway';
            return $gateways;
        });
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