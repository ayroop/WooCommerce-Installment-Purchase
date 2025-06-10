<?php

namespace WooCommerce\InstallmentPurchase\Frontend;

class Frontend {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'render_payment_options'));
        add_action('template_redirect', array($this, 'handle_installment_selection'));
        add_shortcode('installment_application', array($this, 'render_application_form'));
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WC_INSTALLMENT_PURCHASE_URL . 'assets/css/frontend.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            WC_INSTALLMENT_PURCHASE_URL . 'assets/js/frontend.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script($this->plugin_name, 'installmentPurchase', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('installment-purchase-nonce'),
            'i18n' => array(
                'processing' => __('Processing...', 'installment-purchase'),
                'error' => __('An error occurred. Please try again.', 'installment-purchase')
            )
        ));
    }

    public function render_payment_options() {
        global $product;
        
        if (!$product || !$product->is_purchasable()) {
            return;
        }

        $price = $product->get_price();
        $min_down_payment = $price * 0.5; // 50% minimum down payment
        $service_fee = get_option('wc_installment_purchase_service_fee', 5); // Default 5%
        $max_months = get_option('wc_installment_purchase_max_months', 6); // Default 6 months

        ?>
        <div class="installment-purchase-options">
            <h3><?php _e('Payment Method', 'installment-purchase'); ?></h3>
            
            <div class="payment-options">
                <label>
                    <input type="radio" name="payment_method" value="cash" checked>
                    <?php _e('Cash Payment', 'installment-purchase'); ?>
                </label>
                
                <label>
                    <input type="radio" name="payment_method" value="installment">
                    <?php _e('Installment Payment', 'installment-purchase'); ?>
                </label>
            </div>

            <div class="installment-details" style="display: none;">
                <p><?php printf(__('Down Payment: %s', 'installment-purchase'), wc_price($min_down_payment)); ?></p>
                <p><?php printf(__('Monthly Payment: %s x %d months', 'installment-purchase'), 
                    wc_price(($price - $min_down_payment) * (1 + $service_fee/100) / $max_months), 
                    $max_months); ?></p>
                <p><?php printf(__('Service Fee: %s%%', 'installment-purchase'), $service_fee); ?></p>
            </div>
        </div>
        <?php
    }

    public function handle_installment_selection() {
        if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'installment') {
            $application_page_id = get_option('wc_installment_application_page_id');
            if ($application_page_id) {
                wp_safe_redirect(add_query_arg('product_id', get_the_ID(), get_permalink($application_page_id)));
                exit;
            }
        }
    }

    public function render_application_form() {
        if (!is_user_logged_in()) {
            echo '<p class="installment-login-notice">' . 
                __('Please log in to apply for installment purchase.', 'installment-purchase') . 
                '</p>';
            return;
        }

        ?>
        <div class="installment-application-form" style="display: none;">
            <h3><?php _e('Installment Application', 'installment-purchase'); ?></h3>
            
            <form id="installment-application">
                <div class="form-row">
                    <label for="first_name"><?php _e('First Name', 'installment-purchase'); ?></label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>

                <div class="form-row">
                    <label for="last_name"><?php _e('Last Name', 'installment-purchase'); ?></label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>

                <div class="form-row">
                    <label for="bank_account"><?php _e('Bank Account Number', 'installment-purchase'); ?></label>
                    <input type="text" id="bank_account" name="bank_account" required>
                </div>

                <div class="form-row">
                    <label>
                        <input type="checkbox" name="terms" required>
                        <?php _e('I agree to the terms and conditions', 'installment-purchase'); ?>
                    </label>
                </div>

                <div class="form-row">
                    <button type="submit" class="button alt">
                        <?php _e('Submit Application', 'installment-purchase'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
} 