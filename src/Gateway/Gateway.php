<?php

namespace WooCommerce\InstallmentPurchase\Gateway;

class Gateway extends \WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'installment_purchase';
        $this->icon = '';
        $this->has_fields = true;
        $this->method_title = __('Installment Purchase', 'installment-purchase');
        $this->method_description = __('Allow customers to purchase products in installments', 'installment-purchase');

        $this->supports = array(
            'products',
            'refunds'
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->min_down_payment = $this->get_option('min_down_payment', 50);
        $this->service_fee = $this->get_option('service_fee', 5);
        $this->max_months = $this->get_option('max_months', 6);
        $this->inquiry_fee = $this->get_option('inquiry_fee', 100000);

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_installment_purchase_gateway', array($this, 'check_installment_response'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'installment-purchase'),
                'type'    => 'checkbox',
                'label'   => __('Enable Installment Purchase', 'installment-purchase'),
                'default' => 'no'
            ),
            'title' => array(
                'title'       => __('Title', 'installment-purchase'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'installment-purchase'),
                'default'     => __('Installment Purchase', 'installment-purchase'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'installment-purchase'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'installment-purchase'),
                'default'     => __('Pay for your purchase in monthly installments.', 'installment-purchase'),
            ),
            'min_down_payment' => array(
                'title'       => __('Minimum Down Payment (%)', 'installment-purchase'),
                'type'        => 'number',
                'description' => __('Minimum down payment percentage required.', 'installment-purchase'),
                'default'     => '50',
                'desc_tip'    => true,
            ),
            'service_fee' => array(
                'title'       => __('Service Fee (%)', 'installment-purchase'),
                'type'        => 'number',
                'description' => __('Service fee percentage applied to the remaining balance.', 'installment-purchase'),
                'default'     => '5',
                'desc_tip'    => true,
            ),
            'max_months' => array(
                'title'       => __('Maximum Months', 'installment-purchase'),
                'type'        => 'number',
                'description' => __('Maximum number of months for installment payments.', 'installment-purchase'),
                'default'     => '6',
                'desc_tip'    => true,
            ),
            'inquiry_fee' => array(
                'title'       => __('Inquiry Fee', 'installment-purchase'),
                'type'        => 'number',
                'description' => __('Fee for credit check inquiry.', 'installment-purchase'),
                'default'     => '100000',
                'desc_tip'    => true,
            ),
        );
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        // Mark as on-hold
        $order->update_status('on-hold', __('Awaiting installment application approval', 'installment-purchase'));
        
        // Reduce stock levels
        wc_reduce_stock_levels($order_id);
        
        // Remove cart
        WC()->cart->empty_cart();
        
        // Return thankyou redirect
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }

        // Check if the order is an installment purchase
        if ($order->get_payment_method() !== $this->id) {
            return false;
        }

        // Process refund logic here
        // This is a placeholder - implement your refund logic
        
        return true;
    }

    public function check_installment_response() {
        // Handle installment payment notifications
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        
        if ($order_id) {
            $order = wc_get_order($order_id);
            
            if ($order) {
                // Process the payment notification
                // Update order status and installment schedule
                $order->payment_complete();
                
                wp_safe_redirect($this->get_return_url($order));
                exit;
            }
        }
        
        wp_die(__('Invalid request', 'installment-purchase'), __('Payment Error', 'installment-purchase'), array('response' => 400));
    }

    public static function add_gateway($methods) {
        $methods[] = __CLASS__;
        return $methods;
    }
} 