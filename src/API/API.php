<?php

namespace WooCommerce\InstallmentPurchase\API;

class API {
    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('wp_ajax_submit_installment_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_nopriv_submit_installment_application', array($this, 'handle_application_submission'));
    }

    public function register_routes() {
        register_rest_route('wc-installment/v1', '/applications', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_applications'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route('wc-installment/v1', '/applications/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_application'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route('wc-installment/v1', '/applications/(?P<id>\d+)/status', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_application_status'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
    }

    public function check_admin_permission() {
        return current_user_can('manage_woocommerce');
    }

    public function get_applications(\WP_REST_Request $request) {
        global $wpdb;
        
        $applications = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}installment_applications ORDER BY created_at DESC"
        );
        
        return rest_ensure_response($applications);
    }

    public function get_application(\WP_REST_Request $request) {
        global $wpdb;
        
        $application_id = $request->get_param('id');
        
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}installment_applications WHERE id = %d",
            $application_id
        ));
        
        if (!$application) {
            return new \WP_Error('not_found', __('Application not found', 'installment-purchase'), array('status' => 404));
        }
        
        return rest_ensure_response($application);
    }

    public function update_application_status(\WP_REST_Request $request) {
        global $wpdb;
        
        $application_id = $request->get_param('id');
        $status = $request->get_param('status');
        
        if (!in_array($status, array('pending', 'approved', 'declined'))) {
            return new \WP_Error('invalid_status', __('Invalid status', 'installment-purchase'), array('status' => 400));
        }
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'installment_applications',
            array('status' => $status),
            array('id' => $application_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated === false) {
            return new \WP_Error('update_failed', __('Failed to update application status', 'installment-purchase'), array('status' => 500));
        }
        
        // Send notification email
        $this->send_status_notification($application_id, $status);
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Application status updated successfully', 'installment-purchase')
        ));
    }

    public function handle_application_submission() {
        check_ajax_referer('wc-installment-purchase-nonce', 'security');

        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to submit an application', 'installment-purchase')
            ));
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        if (!$product_id || !wc_get_product($product_id)) {
            wp_send_json_error(array(
                'message' => __('Invalid product selected', 'installment-purchase')
            ));
        }

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $bank_account = sanitize_text_field($_POST['bank_account']);

        if (empty($first_name) || empty($last_name) || empty($bank_account)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields', 'installment-purchase')
            ));
        }

        global $wpdb;
        
        $application_id = $wpdb->insert(
            $wpdb->prefix . 'installment_applications',
            array(
                'user_id' => get_current_user_id(),
                'first_name' => $first_name,
                'last_name' => $last_name,
                'bank_account' => $bank_account,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );

        if (!$application_id) {
            wp_send_json_error(array(
                'message' => __('Failed to submit application', 'installment-purchase')
            ));
        }

        // Send notification email
        $this->send_application_notification($application_id);

        wp_send_json_success(array(
            'message' => __('Application submitted successfully', 'installment-purchase'),
            'redirect' => wc_get_checkout_url()
        ));
    }

    private function send_application_notification($application_id) {
        global $wpdb;
        
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}installment_applications WHERE id = %d",
            $application_id
        ));
        
        if (!$application) {
            return;
        }

        $user = get_userdata($application->user_id);
        $admin_email = get_option('admin_email');
        
        // Send to admin
        $admin_subject = sprintf(__('New Installment Application #%d', 'installment-purchase'), $application_id);
        $admin_message = sprintf(
            __('A new installment application has been submitted by %s %s.', 'installment-purchase'),
            $application->first_name,
            $application->last_name
        );
        
        wp_mail($admin_email, $admin_subject, $admin_message);
        
        // Send to user
        $user_subject = __('Your Installment Application', 'installment-purchase');
        $user_message = sprintf(
            __('Dear %s %s,<br><br>Your installment application has been received and is being reviewed. We will notify you once the review is complete.', 'installment-purchase'),
            $application->first_name,
            $application->last_name
        );
        
        wp_mail($user->user_email, $user_subject, $user_message);
    }

    private function send_status_notification($application_id, $status) {
        global $wpdb;
        
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}installment_applications WHERE id = %d",
            $application_id
        ));
        
        if (!$application) {
            return;
        }

        $user = get_userdata($application->user_id);
        
        $subject = sprintf(__('Installment Application #%d Status Update', 'installment-purchase'), $application_id);
        
        switch ($status) {
            case 'approved':
                $message = sprintf(
                    __('Dear %s %s,<br><br>Your installment application has been approved. You can now proceed with the down payment.', 'installment-purchase'),
                    $application->first_name,
                    $application->last_name
                );
                break;
                
            case 'declined':
                $message = sprintf(
                    __('Dear %s %s,<br><br>We regret to inform you that your installment application has been declined.', 'installment-purchase'),
                    $application->first_name,
                    $application->last_name
                );
                break;
                
            default:
                return;
        }
        
        wp_mail($user->user_email, $subject, $message);
    }
} 