<?php

namespace WooCommerce\InstallmentPurchase\Admin;

class Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_installment_details'));
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 3);
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WC_INSTALLMENT_PURCHASE_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts($hook) {
        if ('woocommerce_page_wc-installment-applications' !== $hook) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            WC_INSTALLMENT_PURCHASE_URL . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script($this->plugin_name, 'installmentPurchase', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('installment-purchase-nonce'),
            'i18n' => array(
                'confirmApprove' => __('Are you sure you want to approve this application?', 'installment-purchase'),
                'confirmDecline' => __('Are you sure you want to decline this application?', 'installment-purchase'),
                'processing' => __('Processing...', 'installment-purchase')
            )
        ));
    }

    public function add_menu_pages() {
        add_submenu_page(
            'woocommerce',
            __('Installment Applications', 'installment-purchase'),
            __('Installment Applications', 'installment-purchase'),
            'manage_woocommerce',
            'wc-installment-applications',
            array($this, 'render_applications_page')
        );
    }

    public function register_settings() {
        register_setting('wc_installment_purchase', 'wc_installment_purchase_service_fee');
        register_setting('wc_installment_purchase', 'wc_installment_purchase_max_months');
        register_setting('wc_installment_purchase', 'wc_installment_purchase_inquiry_fee');
    }

    public function render_applications_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_installment_applications';
        $applications = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Installment Applications', 'installment-purchase'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'installment-purchase'); ?></th>
                        <th><?php _e('Customer', 'installment-purchase'); ?></th>
                        <th><?php _e('Bank Account', 'installment-purchase'); ?></th>
                        <th><?php _e('Status', 'installment-purchase'); ?></th>
                        <th><?php _e('Date', 'installment-purchase'); ?></th>
                        <th><?php _e('Actions', 'installment-purchase'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($applications): ?>
                        <?php foreach ($applications as $application): ?>
                            <tr>
                                <td><?php echo esc_html($application->id); ?></td>
                                <td>
                                    <?php 
                                    $user = get_user_by('id', $application->user_id);
                                    echo $user ? esc_html($user->display_name) : __('Unknown', 'installment-purchase');
                                    ?>
                                </td>
                                <td><?php echo esc_html($application->bank_account); ?></td>
                                <td><?php echo esc_html($application->status); ?></td>
                                <td><?php echo esc_html($application->created_at); ?></td>
                                <td>
                                    <?php if ($application->status === 'pending'): ?>
                                        <button class="button approve-application" data-id="<?php echo esc_attr($application->id); ?>">
                                            <?php _e('Approve', 'installment-purchase'); ?>
                                        </button>
                                        <button class="button decline-application" data-id="<?php echo esc_attr($application->id); ?>">
                                            <?php _e('Decline', 'installment-purchase'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <button class="button view-details" data-id="<?php echo esc_attr($application->id); ?>">
                                        <?php _e('View Details', 'installment-purchase'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php _e('No applications found.', 'installment-purchase'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Application Details Modal -->
        <div id="application-details-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2><?php _e('Installment Details', 'installment-purchase'); ?></h2>
                <div id="application-details-content"></div>
            </div>
        </div>
        <?php
    }

    public function display_installment_details($order) {
        $down_payment = $order->get_meta('_installment_down_payment');
        $remaining = $order->get_meta('_installment_remaining');
        $monthly_payment = $order->get_meta('_installment_monthly_payment');
        $months = $order->get_meta('_installment_months');
        
        if (!$down_payment) {
            return;
        }
        
        ?>
        <div class="wc-installment-details">
            <h3><?php _e('Installment Details', 'installment-purchase'); ?></h3>
            
            <p>
                <strong><?php _e('Down Payment:', 'installment-purchase'); ?></strong>
                <?php echo wc_price($down_payment); ?>
            </p>
            
            <p>
                <strong><?php _e('Remaining Balance:', 'installment-purchase'); ?></strong>
                <?php echo wc_price($remaining); ?>
            </p>
            
            <p>
                <strong><?php _e('Monthly Payment:', 'installment-purchase'); ?></strong>
                <?php echo wc_price($monthly_payment); ?>
            </p>
            
            <p>
                <strong><?php _e('Number of Months:', 'installment-purchase'); ?></strong>
                <?php echo esc_html($months); ?>
            </p>
        </div>
        <?php
    }

    public function handle_order_status_change($order_id, $old_status, $new_status) {
        $order = wc_get_order($order_id);
        
        if (!$order->get_meta('_installment_down_payment')) {
            return;
        }
        
        if ($new_status === 'processing') {
            // Create installment schedule
            $this->create_installment_schedule($order);
        }
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
} 