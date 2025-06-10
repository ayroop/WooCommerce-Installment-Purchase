<?php
/**
 * Plugin Name: WooCommerce Installment Purchase
 * Plugin URI: https://your-domain.com/installment-purchase
 * Description: Enable installment purchases for WooCommerce products
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-domain.com
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
if (file_exists(WC_INSTALLMENT_PURCHASE_PATH . 'vendor/autoload.php')) {
    require_once WC_INSTALLMENT_PURCHASE_PATH . 'vendor/autoload.php';
}

// Initialize the plugin
function wc_installment_purchase_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="error">
                <p><?php _e('WooCommerce Installment Purchase requires WooCommerce to be installed and active.', 'installment-purchase'); ?></p>
            </div>
            <?php
        });
        return;
    }

    // Load text domain
    load_plugin_textdomain('installment-purchase', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize plugin
    \WooCommerce\InstallmentPurchase\Core\Plugin::instance();
}
add_action('plugins_loaded', 'wc_installment_purchase_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create database tables
    require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Core/Activator.php';
    \WooCommerce\InstallmentPurchase\Core\Activator::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    require_once WC_INSTALLMENT_PURCHASE_PATH . 'src/Core/Deactivator.php';
    \WooCommerce\InstallmentPurchase\Core\Deactivator::deactivate();
}); 