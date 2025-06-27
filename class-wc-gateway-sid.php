<?php
/**
 * Plugin Name: SiD Secure EFT for WooCommerce
 * Plugin URI: http://www.sidpayment.com
 * Description: Extends WooCommerce with SiD Secure EFT payment gateway.
 * Version: 1.2.0
 * Tested: 6.8
 *
 * Author: Payfast (Pty) Ltd
 *
 * Copyright (c) 2025 Payfast (Pty) Ltd
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Developer: App Inlet (Pty) Ltd
 *
 * WC requires at least: 8.0
 * WC tested up to: 9.9
 */

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'init_wc_sid_class', 0);

function frontend_includes()
{
    include_once 'includes/wc-cart-functions.php';
    include_once 'includes/wc-notice-functions.php';
    include_once 'includes/wc-template-hooks.php';
    include_once 'includes/class-wc-template-loader.php'; // Template Loader
    include_once 'includes/class-wc-frontend-scripts.php'; // Frontend Scripts
    include_once 'includes/class-wc-form-handler.php'; // Form Handlers
    include_once 'includes/class-wc-cart.php'; // The main cart class
    include_once 'includes/class-wc-tax.php'; // Tax class
    include_once 'includes/class-wc-customer.php'; // Customer class
    include_once 'includes/class-wc-shortcodes.php'; // Shortcodes class
    include_once 'includes/class-wc-https.php'; // https Helper
}

if (!defined('WP_CONTENT_URL')) {
    define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
}

if (!defined('WP_PLUGIN_URL')) {
    define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

define("WC_SID_PLUGINPATH", "/" . plugin_basename(dirname(__FILE__)));
define('WC_SID_BASE_URL', WP_PLUGIN_URL . WC_SID_PLUGINPATH);
define('WC_SID_BASE_DIR', WP_PLUGIN_DIR . WC_SID_PLUGINPATH);

require_once 'classes/updater.class.php';

function init_wc_sid_class()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once WC_SID_BASE_DIR . '/includes/SidAPI.php';
    require_once WC_SID_BASE_DIR . '/classes/SidSettings.php';
    require_once WC_SID_BASE_DIR . '/classes/WC_Gateway_Sid.php';

    function add_wc_sid_class($methods)
    {
        $methods[] = 'WC_Gateway_Sid';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_wc_sid_class');

    add_action('woocommerce_blocks_loaded', 'sid_for_woocommerce_blocks_support');

    function sid_for_woocommerce_blocks_support()
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once WC_SID_BASE_DIR . '/class-wc-gateway-sid-blocks-support.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new WC_SiD_Blocks_Support);
                }
            );
        }
    }

    /**
     * Declares support for HPOS.
     *
     * @return void
     */
    function woocommerce_sid_declare_hpos_compatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }

    add_action('before_woocommerce_init', 'woocommerce_sid_declare_hpos_compatibility');
}
