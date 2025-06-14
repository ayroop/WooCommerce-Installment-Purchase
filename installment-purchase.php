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
 *
 * @package WooCommerce_Installment_Purchase
 */

if (!defined('ABSPATH')) {
    exit;
}

// Declare compatibility with High-Performance Order Storage (HPOS)
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

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

// Load text domain
function wc_installment_purchase_load_textdomain() {
    load_plugin_textdomain('installment-purchase', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'wc_installment_purchase_load_textdomain');

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

    // Register the payment gateway here, after WooCommerce is loaded
    add_filter('woocommerce_payment_gateways', function( $gateways ) {
        $gateways[] = 'WooCommerce\\InstallmentPurchase\\Gateway\\Gateway';
        return $gateways;
    });

    $plugin = new WooCommerce\InstallmentPurchase\Core\Plugin();
    $plugin->run();
}
add_action('plugins_loaded', 'wc_installment_purchase_init'); 