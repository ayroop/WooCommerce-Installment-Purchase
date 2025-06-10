<?php
/**
 * Plugin Name: WooCommerce Installment Purchase
 * Plugin URI: https://github.com/ayroop/WooCommerce-Installment-Purchase
 * Description: A WooCommerce extension that allows customers to purchase products in installments
 * Version: 1.0.0
 * Author: Ayroop
 * Author URI: https://ayrop.com
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

if (!defined('WC_INSTALLMENT_PURCHASE_VERSION')) {
    define('WC_INSTALLMENT_PURCHASE_VERSION', '1.0.0');
}

if (!defined('WC_INSTALLMENT_PURCHASE_PATH')) {
    define('WC_INSTALLMENT_PURCHASE_PATH', plugin_dir_path(__FILE__));
}

if (!defined('WC_INSTALLMENT_PURCHASE_URL')) {
    define('WC_INSTALLMENT_PURCHASE_URL', plugin_dir_url(__FILE__));
}

// Autoloader
require_once WC_INSTALLMENT_PURCHASE_PATH . 'vendor/autoload.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('WooCommerce\InstallmentPurchase\Core\Activator', 'activate'));
register_deactivation_hook(__FILE__, array('WooCommerce\InstallmentPurchase\Core\Deactivator', 'deactivate'));

// Initialize plugin
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

    $plugin = new WooCommerce\InstallmentPurchase\Core\Plugin();
    $plugin->run();
}
add_action('plugins_loaded', 'wc_installment_purchase_init'); 