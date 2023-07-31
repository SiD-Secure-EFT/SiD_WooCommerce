<?php
/**
 * Plugin Name: SiD Secure EFT for WooCommerce
 * Plugin URI: http://www.sidpayment.com
 * Description: Extends WooCommerce with SiD Secure EFT payment gateway.
 * Version: 1.0.4
 * Tested: 6.2.2
 *
 * Author: SiD Secure EFT (Pty) Ltd
 *
 * Copyright (c) 2023 SiD Secure EFT
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Developer: App Inlet (Pty) Ltd
 *
 * WC requires at least: 6.0
 * WC tested up to: 7.9.0
 */
require_once "includes/SidAPI.php";

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'init_wc_sid_class', 0 );

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

if ( !defined( 'WP_CONTENT_URL' ) ) {
    define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
}

if ( !defined( 'WP_PLUGIN_URL' ) ) {
    define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
}

if ( !defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( !defined( 'WP_PLUGIN_DIR' ) ) {
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

define( "WC_SID_PLUGINPATH", "/" . plugin_basename( dirname( __FILE__ ) ) );
define( 'WC_SID_BASE_URL', WP_PLUGIN_URL . WC_SID_PLUGINPATH );
define( 'WC_SID_BASE_DIR', WP_PLUGIN_DIR . WC_SID_PLUGINPATH );

require_once 'classes/updater.class.php';

function init_wc_sid_class()
{

    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    class WC_Gateway_Sid extends WC_Payment_Gateway
    {

        const SID_STATUS_COMPLETED = 'COMPLETED';
        const SID_STATUS_CANCELLED = 'CANCELLED';

        protected $config;

        public function __construct()
        {
            $this->method_title       = __( 'SiD Secure EFT', 'sid' );
            $this->method_description = 'SiD Secure EFT works by sending the customer to the SiD gateway';
            $this->init_settings();

            $this->init_form_fields();

            $this->id           = 'sid';
            $this->icon         = WC_SID_BASE_URL . '/assets/images/logo.png';
            $this->title        = $this->get_option( 'title' );
            $this->has_fields   = false;
            $this->method_title = $this->title;
            $this->description  = $this->get_option( 'description' );
            $this->form_url     = 'https://www.sidpayment.com/paySID/';

            $this->merchant_code = $this->get_option( 'merchant_code' );
            $this->username      = $this->get_option( 'username' );
            $this->password      = $this->get_option( 'password' );
            $this->private_key   = $this->get_option( 'private_key' );

            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            add_action( 'woocommerce_api_wc_gateway_sid', array( $this, 'check_sid_response' ) );

            if ( !$this->is_valid_for_use() ) {
                $this->enabled = false;
            }

            /**
             * Configure and run Github updater
             */
            /** @var  config */
            $this->config = array(
                'slug'               => plugin_basename( __FILE__ ),
                'proper_folder_name' => 'sid_woocommerce',
                'api_url'            => 'https://api.github.com/repos/SiD-Secure-EFT/SiD_WooCommerce',
                'raw_url'            => 'https://raw.github.com/SiD-Secure-EFT/SiD_WooCommerce/master',
                'github_url'         => 'https://github.com/SiD-Secure-EFT/SiD_WooCommerce',
                'zip_url'            => 'https://github.com/SiD-Secure-EFT/SiD_WooCommerce/archive/master.zip',
                'homepage'           => 'https://github.com/SiD-Secure-EFT/SiD_WooCommerce',
                'sslverify'          => true,
                'requires'           => '5.9.0',
                'tested'             => '6.2.2',
                'readme'             => 'README.md',
                'access_token'       => '',
            );
            new WP_GitHub_Updater_SiDWC( $this->config );
        }

        function is_valid_for_use()
        {
            return get_woocommerce_currency() == 'ZAR';
        }

        public function admin_options()
        {
            echo '<h2>' . __( 'SiD Secure EFT', 'woocommerce' ) . '</h2>';
            if ( $this->is_valid_for_use() ) {
                echo '<table class="form-table">';
                echo $this->generate_settings_html();
                echo '</table>';
            } else {
                echo '<div class="inline error"><p><strong>' . _e( 'Gateway Disabled', 'woocommerce' ) . '</strong>:' . _e( 'SiD Secure EFT does not support your store currency.', 'woocommerce' ) . '</p></div>';
            }
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled'       => array(
                    'title'   => __( 'Enable/Disable', 'woocommerce' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable SiD Secure EFT', 'woocommerce' ),
                    'default' => 'yes',
                ),
                'title'         => array(
                    'title'       => __( 'Title', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default'     => __( 'SiD Secure EFT', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'description'   => array(
                    'title'   => __( 'Description', 'woocommerce' ),
                    'type'    => 'textarea',
                    'default' => 'Pay securely using online banking. No credit card required.',
                ),
                'merchant_code' => array(
                    'title'   => __( 'SiD Secure EFT Merchant Code', 'woocommerce' ),
                    'type'    => 'text',
                    'default' => '',
                ),
                'username'      => array(
                    'title'   => __( 'SiD Secure EFT Order Query Web Service Username', 'woocommerce' ),
                    'type'    => 'text',
                    'default' => '',
                ),
                'password'      => array(
                    'title'   => __( 'SiD Secure EFT Order Query Web Service Password', 'woocommerce' ),
                    'type'    => 'text',
                    'default' => '',
                ),
                'private_key'   => array(
                    'title'   => __( 'SiD Secure EFT Private Key', 'woocommerce' ),
                    'type'    => 'text',
                    'default' => '',
                ),
            );
        }

        function get_sid_args( $order_id )
        {
            global $woocommerce;
            $order = new WC_Order( $order_id );

            $order_id      = $order->get_id();
            $order_total   = $order->get_total();
            $currency      = get_woocommerce_currency();
            $merchant_code = $this->merchant_code;
            $order_key     = $order->get_order_key();
            $private_key   = $this->private_key;

            $consistent = strtoupper( hash( "sha512", $merchant_code . $currency . "ZA" . $order_id . $order_total . $order_key . $private_key ) );
            //SiD Secure EFT Args
            $args_array = array(
                'SID_MERCHANT'   => $merchant_code,
                'SID_CURRENCY'   => $currency,
                'SID_COUNTRY'    => "ZA",
                'SID_REFERENCE'  => $order_id,
                'SID_AMOUNT'     => $order_total,
                'SID_CUSTOM_01'  => $order_key,
                'SID_CONSISTENT' => $consistent,
            );

            return $args_array;
        }

        function process_payment( $order_id )
        {
            $order    = wc_get_order( $order_id );
            $sid_args = $this->get_sid_args( $order_id );
            $url_args = http_build_query( $sid_args, '', '&' );

            return array(
                'result'   => 'success',
                'redirect' => $this->form_url . '?' . $url_args,
            );
        }

        function check_sid_response()
        {
            if ( !$this->process_sid_response() ) {
                wp_redirect( get_permalink( get_option( 'woocommerce_checkout_page_id' ) ) );
                exit();
            }
        }

        private function process_sid_response()
        {
            global $woocommerce;

            $sid_status     = strtoupper( $_REQUEST["SID_STATUS"] );
            $sid_merchant   = $_REQUEST["SID_MERCHANT"];
            $sid_country    = $_REQUEST["SID_COUNTRY"];
            $sid_currency   = $_REQUEST["SID_CURRENCY"];
            $sid_reference  = $_REQUEST["SID_REFERENCE"];
            $sid_amount     = $_REQUEST["SID_AMOUNT"];
            $sid_bank       = $_REQUEST["SID_BANK"];
            $sid_date       = $_REQUEST["SID_DATE"];
            $sid_receiptno  = $_REQUEST["SID_RECEIPTNO"];
            $sid_tnxid      = $_REQUEST["SID_TNXID"];
            $sid_custom_01  = $_REQUEST["SID_CUSTOM_01"];
            $sid_custom_02  = $_REQUEST["SID_CUSTOM_02"];
            $sid_custom_03  = $_REQUEST["SID_CUSTOM_03"];
            $sid_custom_04  = $_REQUEST["SID_CUSTOM_04"];
            $sid_custom_05  = $_REQUEST["SID_CUSTOM_05"];
            $sid_consistent = $_REQUEST["SID_CONSISTENT"];

            $consistent_check = strtoupper( hash( 'sha512', $sid_status . $sid_merchant . $sid_country . $sid_currency
                . $sid_reference . $sid_amount . $sid_bank . $sid_date . $sid_receiptno
                . $sid_tnxid . $sid_custom_01 . $sid_custom_02 . $sid_custom_03 . $sid_custom_04
                . $sid_custom_05 . $this->private_key ) );

            if ( $consistent_check != $sid_consistent ) {
                wc_add_notice( $sid_status, 'error' );
                return false;
            }

            $order_id = $sid_reference;
            $order    = new WC_Order( $order_id );

            $queryData = [
                "sellerReference"   => $order_id,
                "startDate"          => $order->get_date_created()->date("Y-m-d"),
                "endDate"            => date("Y-m-d")
            ];

            $sidAPI = new SidAPI($queryData, $this->username, $this->password);

            if (floatval( $order->get_total() ) != floatval( $sid_amount )
                || get_woocommerce_currency() != strtoupper( $sid_currency )
            ) {
                $error_msg = sprintf( __( 'Validation error: SiD Secure EFT payment amount (%1s %2s) does not match order amount.', 'woocommerce' ), $sid_currency, $sid_amount );
                $order->update_status( 'on-hold', $error_msg );
                wc_add_notice( 'Validation error: SiD Secure EFT payment amounts do not match order amount.', $notice_type = 'error' );
                return false;
            }
            if (strtoupper( $sid_status ) == self::SID_STATUS_CANCELLED
                || $sidAPI->retrieveTransaction()->status !== "COMPLETED"
            ) {
                $cancelled_message = sprintf( __( 'Payment %s.', 'woocommerce' ), $sid_status );
                $order->update_status( 'failed', $cancelled_message );
                $order->add_order_note( "Failed payment from SiD Secure EFT (TNXID: $sid_tnxid)" );
                wc_add_notice( 'Your transaction has failed.', $notice_type = 'error' );
                return false;
            } elseif (strtoupper( $sid_status ) == self::SID_STATUS_COMPLETED) {
                if ( !in_array( strtolower( $order->get_status() ), array( 'completed', 'processing' ) ) ) {
                    $order->add_order_note( "Success payment from SiD Secure EFT (TNXID: $sid_tnxid)" );
                    $order->payment_complete();
                    $order->reduce_order_stock();
                }

                $thankyou_url = add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) );
                wp_redirect( $thankyou_url );

                exit();
            }
        }
    }

    function add_wc_sid_class( $methods )
    {
        $methods[] = 'WC_Gateway_Sid';
        return $methods;
    }

    add_filter( 'woocommerce_payment_gateways', 'add_wc_sid_class' );

    if ( is_admin() ) {
        new WC_Gateway_Sid();
    }
}
