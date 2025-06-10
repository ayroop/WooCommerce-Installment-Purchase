<?php
/**
 * Plugin Name: WooCommerce Installment Purchase
 * Plugin URI: https://github.com/ayroop/WooCommerce-Installment-Purchase
 * Description: Enable installment purchases for WooCommerce products with smart calculation and verification system.
 * Version: 1.0.0
 * Author: Ayroop
 * Author URI: https://github.com/ayroop
 * Text Domain: installment-purchase
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_INSTALLMENT_PURCHASE_VERSION', '1.0.0');
define('WC_INSTALLMENT_PURCHASE_FILE', __FILE__);
define('WC_INSTALLMENT_PURCHASE_PATH', plugin_dir_path(__FILE__));
define('WC_INSTALLMENT_PURCHASE_URL', plugin_dir_url(__FILE__));

// Autoloader
require_once WC_INSTALLMENT_PURCHASE_PATH . 'vendor/autoload.php';

// Check if WooCommerce is active
function wc_installment_purchase_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="error">
                <p><?php _e('WooCommerce Installment Purchase requires WooCommerce to be installed and active.', 'installment-purchase'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}

// Initialize the plugin
function wc_installment_purchase_init() {
    if (!wc_installment_purchase_check_woocommerce()) {
        return;
    }

    // Load text domain
    load_plugin_textdomain('installment-purchase', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize plugin
    $plugin = new \WooCommerce\InstallmentPurchase\Core\Plugin();
    $plugin->run();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, function() {
    if (!wc_installment_purchase_check_woocommerce()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('WooCommerce Installment Purchase requires WooCommerce to be installed and active.', 'installment-purchase'));
    }
    
    $activator = new \WooCommerce\InstallmentPurchase\Core\Activator();
    $activator->activate();
});

register_deactivation_hook(__FILE__, function() {
    $deactivator = new \WooCommerce\InstallmentPurchase\Core\Deactivator();
    $deactivator->deactivate();
});

// Hook into WordPress
add_action('plugins_loaded', 'wc_installment_purchase_init'); 