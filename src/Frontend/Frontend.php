<?php

namespace WooCommerce\InstallmentPurchase\Frontend;

class Frontend {
    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'render_purchase_options'));
        add_action('template_redirect', array($this, 'handle_installment_selection'));
        add_shortcode('installment_application', array($this, 'render_application_form'));
    }

    public function enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_style(
                'wc-installment-purchase',
                WC_INSTALLMENT_PURCHASE_URL . 'assets/css/frontend.css',
                array(),
                WC_INSTALLMENT_PURCHASE_VERSION
            );

            wp_enqueue_script(
                'wc-installment-purchase',
                WC_INSTALLMENT_PURCHASE_URL . 'assets/js/frontend.js',
                array('jquery'),
                WC_INSTALLMENT_PURCHASE_VERSION,
                true
            );

            wp_localize_script('wc-installment-purchase', 'wcInstallmentPurchase', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc-installment-purchase-nonce'),
                'i18n' => array(
                    'selectPaymentMethod' => __('Please select a payment method', 'installment-purchase'),
                    'processing' => __('Processing...', 'installment-purchase'),
                )
            ));
        }
    }

    public function render_purchase_options() {
        global $product;
        
        if (!$product || !$product->is_purchasable()) {
            return;
        }

        $price = $product->get_price();
        $min_down_payment = get_option('wc_installment_min_down_payment', 50);
        $service_fee = get_option('wc_installment_service_fee', 5);
        $max_months = get_option('wc_installment_max_months', 6);

        $down_payment = ($price * $min_down_payment) / 100;
        $remaining = $price - $down_payment;
        $monthly_payment = ($remaining * (1 + ($service_fee / 100))) / $max_months;

        ?>
        <div class="wc-installment-purchase-options">
            <h4><?php _e('Payment Method', 'installment-purchase'); ?></h4>
            
            <div class="payment-options">
                <label>
                    <input type="radio" name="payment_method" value="cash" checked>
                    <?php _e('Cash Payment', 'installment-purchase'); ?>
                    <span class="price"><?php echo wc_price($price); ?></span>
                </label>

                <label>
                    <input type="radio" name="payment_method" value="installment">
                    <?php _e('Installment Payment', 'installment-purchase'); ?>
                    <div class="installment-details">
                        <p><?php printf(__('Down Payment: %s', 'installment-purchase'), wc_price($down_payment)); ?></p>
                        <p><?php printf(__('Monthly Payment: %s x %d months', 'installment-purchase'), wc_price($monthly_payment), $max_months); ?></p>
                        <p><?php printf(__('Service Fee: %s%%', 'installment-purchase'), $service_fee); ?></p>
                    </div>
                </label>
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

    public function render_application_form($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to apply for installment purchase.', 'installment-purchase') . '</p>';
        }

        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        if (!$product_id || !wc_get_product($product_id)) {
            return '<p>' . __('Invalid product selected.', 'installment-purchase') . '</p>';
        }

        ob_start();
        ?>
        <form id="installment-application-form" class="wc-installment-application-form">
            <?php wp_nonce_field('wc-installment-application', 'wc_installment_nonce'); ?>
            <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">

            <div class="form-row">
                <label for="first_name"><?php _e('First Name', 'installment-purchase'); ?> *</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <div class="form-row">
                <label for="last_name"><?php _e('Last Name', 'installment-purchase'); ?> *</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <div class="form-row">
                <label for="bank_account"><?php _e('Bank Account Number', 'installment-purchase'); ?> *</label>
                <input type="text" id="bank_account" name="bank_account" required>
            </div>

            <div class="form-row">
                <label>
                    <input type="checkbox" name="terms" required>
                    <?php _e('I agree to the terms and conditions', 'installment-purchase'); ?> *
                </label>
            </div>

            <div class="form-row">
                <button type="submit" class="button alt">
                    <?php _e('Submit Application', 'installment-purchase'); ?>
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
} 