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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('woocommerce_before_cart', array($this, 'render_application_form')); 
        add_shortcode('installment_down_payment_form', array($this, 'render_down_payment_form'));

        // New hook for displaying installment details on checkout
        add_action('woocommerce_review_order_after_payment_method_options', array($this, 'render_installment_details_on_checkout'));
        add_action('woocommerce_checkout_update_order_review', array($this, 'ajax_calculate_installments'));
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
                'selectInstallmentPlan' => __('Please select an installment plan.', 'installment-purchase'),
                'invalidEmail' => __('Please enter a valid email address.', 'installment-purchase'),
                'invalidPhone' => __('Please enter a valid phone number.', 'installment-purchase'),
                'acceptTerms' => __('You must accept the terms and conditions.', 'installment-purchase'),
                'installmentPlanDetails' => __('Installment Plan Details', 'installment-purchase'),
                'downPayment' => __('Down Payment', 'installment-purchase'),
                'remainingBalance' => __('Remaining Balance', 'installment-purchase'),
                'serviceFee' => __('Service Fee', 'installment-purchase'),
                'monthlyInstallmentOptions' => __('Monthly Installment Options', 'installment-purchase'),
                'selectMonths' => __('Select months', 'installment-purchase'),
                'processing' => __('Processing...', 'installment-purchase'),
                'error' => __('An error occurred. Please try again.', 'installment-purchase')
            )
        ));
    }

    public function render_purchase_options() {
        // This method is no longer hooked to woocommerce_before_add_to_cart_button
        // Its logic will be integrated into render_installment_details_on_checkout for the checkout page.
    }

    public function render_application_form() {
        // Render a simplified application form on checkout/cart
        // This form is now conditionally displayed by JavaScript on checkout.
        if (is_checkout() || is_cart()) {
            echo '<div class="installment-application-form" style="display: none;">'; 
            echo '<h3>' . __('Installment Application Form', 'installment-purchase') . '</h3>';
            echo '<form id="installment-application-submit-form">';
            echo '<p>';
            echo '<label for="bank_account">' . __('Bank Account Number:', 'installment-purchase') . '</label>';
            echo '<input type="text" id="bank_account" name="bank_account" required>';
            echo '</p>';
            echo '<p>';
            echo '<label for="phone_number">' . __('Phone Number:', 'installment-purchase') . '</label>';
            echo '<input type="text" id="phone_number" name="phone_number" required>';
            echo '</p>';
            echo '<p>';
            echo '<label for="email_address">' . __('Email Address:', 'installment-purchase') . '</label>';
            echo '<input type="email" id="email_address" name="email_address" required>';
            echo '</p>';
            echo '<p>';
            echo '<input type="checkbox" id="terms_and_conditions" name="terms_and_conditions" required>';
            echo '<label for="terms_and_conditions">' . __('I accept the terms and conditions', 'installment-purchase') . '</label>';
            echo '</p>';
            echo '<button type="submit">' . __('Submit Application', 'installment-purchase') . '</button>';
            echo '</form>';
            echo '</div>';
        }
    }

    public function render_down_payment_form($atts) {
        if (!isset($_GET['order_id']) || !isset($_GET['key'])) {
            return '<p>' . __('Invalid request for down payment.', 'installment-purchase') . '</p>';
        }

        $order_id = absint($_GET['order_id']);
        $order_key = sanitize_text_field($_GET['key']);
        $order = wc_get_order($order_id);

        if (!$order || $order->get_order_key() !== $order_key || $order->get_status() !== 'pending-down-payment') {
            return '<p>' . __('Order not found or invalid for down payment.', 'installment-purchase') . '</p>';
        }

        $down_payment_amount = (float) $order->get_meta('_installment_down_payment_amount');

        ob_start();
        ?>
        <div class="woocommerce-info">
            <?php echo sprintf( __('Your down payment for order #%s is %s. Please choose a payment method below.', 'installment-purchase'), $order->get_id(), wc_price( $down_payment_amount ) ); ?>
        </div>

        <form id="down-payment-form" method="post" class="woocommerce-checkout" action="">
            <div id="payment" class="woocommerce-checkout-payment">
                <ul class="wc_payment_methods payment_methods methods">
                    <?php
                    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
                    if ( $available_gateways ) {
                        foreach ( $available_gateways as $gateway ) {
                            // Exclude our own installment gateway from down payment options
                            if ( $gateway->id === 'installment_purchase' ) {
                                continue;
                            }
                            ?>
                            <li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?>">
                                <input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio" name="payment_method_for_down_payment" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->id, WC()->session->get( 'chosen_payment_method' ) ); ?> data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />
                                <label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
                                    <?php echo esc_html( $gateway->get_title() ); ?> <?php echo $gateway->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </label>
                                <?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
                                    <div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>" <?php if ( ! $gateway->chosen ) : ?>style="display:none;"<?php endif; ?> >
                                        <?php $gateway->payment_fields(); ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                            <?php
                        }
                    } else {
                        echo '<p class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . __('No payment methods available for down payment.', 'installment-purchase') . '</p>';
                    }
                    ?>
                </ul>

                <div class="form-row place-order">
                    <input type="hidden" name="woocommerce_down_payment_proceed" value="1" />
                    <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>" />
                    <input type="hidden" name="order_key" value="<?php echo esc_attr($order_key); ?>" />
                    <?php wp_nonce_field( 'woocommerce-down_payment-nonce', 'woocommerce-down_payment-nonce-field' ); ?>
                    <button type="submit" class="button alt" id="place_down_payment" value="<?php esc_attr_e( 'Pay Down Payment', 'installment-purchase' ); ?>" data-value="<?php esc_attr_e( 'Pay Down Payment', 'installment-purchase' ); ?>">
                        <?php esc_html_e( 'Pay Down Payment', 'installment-purchase' ); ?>
                    </button>
                </div>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    public function render_installment_details_on_checkout() {
        // This method will display installment details if our gateway is selected
        // The actual calculation will be done via AJAX for dynamic updates.
        if ( 'installment_purchase' === WC()->session->get( 'chosen_payment_method' ) ) {
            $cart_total = WC()->cart->get_total('edit');
            $min_down_payment_percentage = (float) get_option('wc_installment_purchase_service_fee', 2.5);
            $max_months = (int) get_option('wc_installment_purchase_max_months', 24);
            $service_fee_percentage = (float) get_option('wc_installment_purchase_service_fee', 2.5);

            $down_payment_amount = ($cart_total * $min_down_payment_percentage) / 100;
            $remaining_balance = $cart_total - $down_payment_amount;
            
            ob_start();
            ?>
            <div class="installment-checkout-details">
                <h3><?php _e('Installment Plan Details', 'installment-purchase'); ?></h3>
                <p><?php echo sprintf(__('Down Payment: %s', 'installment-purchase'), wc_price($down_payment_amount)); ?></p>
                <p><?php echo sprintf(__('Remaining Balance: %s', 'installment-purchase'), wc_price($remaining_balance)); ?></p>
                <p><?php echo sprintf(__('Service Fee: %s%%', 'installment-purchase'), $service_fee_percentage); ?></p>
                <div id="installment-monthly-options">
                    <h4><?php _e('Monthly Installment Options', 'installment-purchase'); ?></h4>
                    <select name="selected_installment_months" id="selected_installment_months">
                        <option value="">-- <?php _e('Select months', 'installment-purchase'); ?> --</option>
                        <?php for ($i = 1; $i <= $max_months; $i++) :
                            $monthly_payment = ($remaining_balance * (1 + ($service_fee_percentage / 100))) / $i;
                            ?>
                            <option value="<?php echo esc_attr($i); ?>">
                                <?php echo sprintf(__('%d months: %s/month', 'installment-purchase'), $i, wc_price($monthly_payment)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <p class="installment-total-summary"></p>
                </div>
            </div>
            <?php
            echo ob_get_clean();
        }
    }
    
    public function ajax_calculate_installments() {
        if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) || ! WC_AJAX || ! isset( $_POST['woocommerce_checkout_update_order_review'] ) ) {
            return;
        }

        if ( 'installment_purchase' === WC()->session->get( 'chosen_payment_method' ) ) {
            $cart_total = WC()->cart->get_total('edit');
            $min_down_payment_percentage = (float) get_option('wc_installment_purchase_service_fee', 2.5);
            $max_months = (int) get_option('wc_installment_purchase_max_months', 24);
            $service_fee_percentage = (float) get_option('wc_installment_purchase_service_fee', 2.5);

            $down_payment_amount = ($cart_total * $min_down_payment_percentage) / 100;
            $remaining_balance = $cart_total - $down_payment_amount;

            $options_html = '<option value="">-- ' . __('Select months', 'installment-purchase') . ' --</option>';
            for ($i = 1; $i <= $max_months; $i++) {
                $monthly_payment = ($remaining_balance * (1 + ($service_fee_percentage / 100))) / $i;
                $options_html .= '<option value="' . esc_attr($i) . '">' . sprintf(__('%d months: %s/month', 'installment-purchase'), $i, wc_price($monthly_payment)) . '</option>';
            }
            
            wp_send_json( array(
                'down_payment_amount' => wc_price($down_payment_amount),
                'remaining_balance' => wc_price($remaining_balance),
                'service_fee_percentage' => $service_fee_percentage,
                'options_html' => $options_html,
                'success' => true
            ) );
        } else {
            wp_send_json_success();
        }
    }
} 