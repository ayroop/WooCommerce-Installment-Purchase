<?php

namespace WooCommerce\InstallmentPurchase\Gateway;

class Gateway extends \WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'installment_purchase';
        $this->icon = '';
        $this->has_fields = true;
        $this->method_title = __('Installment Purchase', 'installment-purchase');
        $this->method_description = __('Enable customers to purchase products in installments', 'installment-purchase');

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
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
        
        // Calculate down payment
        $total = $order->get_total();
        $down_payment = ($total * $this->min_down_payment) / 100;
        
        // Update order meta
        $order->update_meta_data('_installment_down_payment', $down_payment);
        $order->update_meta_data('_installment_remaining', $total - $down_payment);
        $order->update_meta_data('_installment_months', $this->max_months);
        $order->update_meta_data('_installment_service_fee', $this->service_fee);
        
        // Calculate monthly payment
        $monthly_payment = ($total - $down_payment) * (1 + ($this->service_fee / 100)) / $this->max_months;
        $order->update_meta_data('_installment_monthly_payment', $monthly_payment);
        
        // Create installment schedule
        $this->create_installment_schedule($order);
        
        // Mark as on-hold
        $order->update_status('on-hold', __('Awaiting down payment', 'installment-purchase'));
        
        // Empty cart
        WC()->cart->empty_cart();
        
        // Return thankyou redirect
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    private function create_installment_schedule($order) {
        global $wpdb;
        
        $remaining = $order->get_meta('_installment_remaining');
        $monthly_payment = $order->get_meta('_installment_monthly_payment');
        $months = $order->get_meta('_installment_months');
        
        $due_date = new \DateTime();
        $due_date->modify('+1 month');
        
        for ($i = 0; $i < $months; $i++) {
            $wpdb->insert(
                $wpdb->prefix . 'installment_schedules',
                array(
                    'application_id' => $order->get_id(),
                    'amount' => $monthly_payment,
                    'due_date' => $due_date->format('Y-m-d'),
                    'status' => 'pending'
                ),
                array('%d', '%f', '%s', '%s')
            );
            
            $due_date->modify('+1 month');
        }
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
} 