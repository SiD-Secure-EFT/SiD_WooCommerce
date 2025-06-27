<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once "SidSettings.php";

class WC_Gateway_Sid extends WC_Payment_Gateway
{

    const SID_STATUS_COMPLETED = 'COMPLETED';
    const SID_STATUS_CANCELLED = 'CANCELLED';

    protected $config;

    public function __construct()
    {
        $this->id                 = 'sid';
        $this->method_title       = __('SiD Secure EFT', 'sid_woocommerce');
        $this->method_description = __(
            'SiD Secure EFT works by sending the customer to the SiD gateway, ',
            'sid_woocommerce'
        );
        $this->init_settings();

        $this->init_form_fields();

        $this->icon         = WC_SID_BASE_URL . '/assets/images/logo.png';
        $this->title        = $this->get_option('title');
        $this->has_fields   = false;
        $this->method_title = $this->title;
        $this->description  = $this->get_option('description');
        $this->form_url     = 'https://www.sidpayment.com/paySID/';

        $this->merchant_code = $this->get_option('merchant_code');
        $this->username      = $this->get_option('username');
        $this->password      = $this->get_option('password');
        $this->private_key   = $this->get_option('private_key');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options')
        );

        add_action('woocommerce_api_wc_gateway_sid', array($this, 'check_sid_response'));

        if (!$this->is_valid_for_use()) {
            $this->enabled = false;
        }

        /**
         * Configure and run Github updater
         */
        /** @var  config */
        $this->config = array(
            'slug'               => plugin_basename(__FILE__),
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
        new WP_GitHub_Updater_SiDWC($this->config);
    }

    public function init_form_fields()
    {
        $sidSettings       = new SidSettings();
        $this->form_fields = $sidSettings::get_settings();
    }

    public function is_valid_for_use()
    {
        return get_woocommerce_currency() == 'ZAR';
    }

    public function admin_options(): void
    {
        echo '<h2>' . esc_html__('SiD Secure EFT', 'sid_woocommerce') . '</h2>';
        $this->showAdminOptions();
    }

    /**
     * @return void
     */
    protected function showAdminOptions(): void
    {
        if ($this->is_valid_for_use()) {
            echo '<table class="form-table">';
            echo wp_kses_post($this->generate_settings_html());
            echo '</table>';
        } else {
            echo '<div class="inline error"><p><strong>';
            esc_html_e('Gateway Disabled', 'sid_woocommerce');
            echo ':</strong> ';
            esc_html_e('SiD Secure EFT does not support your store currency.', 'sid_woocommerce');
            echo '</p></div>';
        }
    }

    public function process_payment($order_id)
    {
        $sid_args = $this->get_sid_args($order_id);
        $url_args = http_build_query($sid_args, '', '&');

        return array(
            'result'   => 'success',
            'redirect' => $this->form_url . '?' . $url_args,
        );
    }

    public function get_sid_args($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);

        $order_id      = $order->get_id();
        $order_total   = $order->get_total();
        $currency      = get_woocommerce_currency();
        $merchant_code = $this->merchant_code;
        $order_key     = $order->get_order_key();
        $private_key   = $this->private_key;

        $consistent = strtoupper(
            hash(
                "sha512",
                $merchant_code . $currency . "ZA" . $order_id . $order_total . $order_key . $private_key
            )
        );

        //SiD Secure EFT Args
        return [
            'SID_MERCHANT'   => $merchant_code,
            'SID_CURRENCY'   => $currency,
            'SID_COUNTRY'    => "ZA",
            'SID_REFERENCE'  => $order_id,
            'SID_AMOUNT'     => $order_total,
            'SID_CUSTOM_01'  => $order_key,
            'SID_CONSISTENT' => $consistent,
        ];
    }

    public function check_sid_response()
    {
        if (!$this->process_sid_response()) {
            wp_redirect(get_permalink(get_option('woocommerce_checkout_page_id')));
            exit();
        }
    }

    private function process_sid_response()
    {
        global $woocommerce;

        $sid_status     = $this->get_request_value('SID_STATUS', true);
        $sid_merchant   = $this->get_request_value('SID_MERCHANT');
        $sid_country    = $this->get_request_value('SID_COUNTRY');
        $sid_currency   = $this->get_request_value('SID_CURRENCY');
        $sid_reference  = $this->get_request_value('SID_REFERENCE');
        $sid_amount     = $this->get_request_value('SID_AMOUNT');
        $sid_bank       = $this->get_request_value('SID_BANK');
        $sid_date       = $this->get_request_value('SID_DATE');
        $sid_receiptno  = $this->get_request_value('SID_RECEIPTNO');
        $sid_tnxid      = $this->get_request_value('SID_TNXID');
        $sid_custom_01  = $this->get_request_value('SID_CUSTOM_01');
        $sid_custom_02  = $this->get_request_value('SID_CUSTOM_02');
        $sid_custom_03  = $this->get_request_value('SID_CUSTOM_03');
        $sid_custom_04  = $this->get_request_value('SID_CUSTOM_04');
        $sid_custom_05  = $this->get_request_value('SID_CUSTOM_05');
        $sid_consistent = $this->get_request_value('SID_CONSISTENT');


        $consistent_check = strtoupper(
            hash(
                'sha512',
                $sid_status . $sid_merchant . $sid_country . $sid_currency
                . $sid_reference . $sid_amount . $sid_bank . $sid_date . $sid_receiptno
                . $sid_tnxid . $sid_custom_01 . $sid_custom_02 . $sid_custom_03 . $sid_custom_04
                . $sid_custom_05 . $this->private_key
            )
        );

        if ($consistent_check != $sid_consistent) {
            wc_add_notice(
                __('Validation error: SiD Secure EFT consistent check failed.', 'sid_woocommerce'),
                'error'
            );

            return false;
        }

        $order_id = $sid_reference;
        $order    = new WC_Order($order_id);

        $queryData = [
            "sellerReference" => $order_id,
            "startDate"       => $order->get_date_created()->date("Y-m-d"),
            "endDate"         => gmdate("Y-m-d")
        ];

        try {
            $sidAPI = new SidAPI($queryData, $this->username, $this->password);
        } catch (\Throwable $e) {
            $order->update_status('on-hold', __('Error connecting to SiD Secure EFT API.', 'sid_woocommerce'));
            wc_add_notice(
                __('Error connecting to SiD Secure EFT API: ' . $e->getMessage(), 'sid_woocommerce'),
                'error'
            );
            return false;
        }

        if (floatval($order->get_total()) != floatval($sid_amount)
            || get_woocommerce_currency() != strtoupper($sid_currency)) {
            // translators: %1$s is the currency code, %2$s is the payment amount.
            $error_msg = sprintf(
                __(
                    'Validation error: SiD Secure EFT payment amount (%1$s %2$s) does not match order amount.',
                    'sid_woocommerce'
                ),
                $sid_currency,
                $sid_amount
            );

            $order->update_status('on-hold', $error_msg);
            wc_add_notice(
                __('Validation error: SiD Secure EFT payment amounts do not match order amount.', 'sid_woocommerce'),
                'error'
            );

            return false;
        }

        $sid_status_upper   = strtoupper($sid_status);
        $order_status_lower = strtolower($order->get_status());
        $transaction_status = $sidAPI->retrieveTransaction()->status;

        // Handle failure
        if (
            $sid_status_upper === self::SID_STATUS_CANCELLED ||
            $transaction_status !== 'COMPLETED'
        ) {
            // translators: %s is the SiD payment status.
            $message = sprintf(__('Payment %s.', 'sid_woocommerce'), $sid_status);
            $order->update_status('failed', $message);
            $order->add_order_note("Failed payment from SiD Secure EFT (TNXID: $sid_tnxid)");
            wc_add_notice('Your transaction has failed.', 'error');
            return false;
        }

        // Handle success
        $this->handle_success_order($sid_status_upper, $order_status_lower, $order, $sid_tnxid);
        exit;
    }

    /**
     * Get and sanitize a value from the $_REQUEST superglobal.
     *
     * @param string $key The request key.
     * @param bool $to_upper Whether to convert the result to uppercase.
     * @return string The sanitized request value or empty string if not set.
     */
    protected function get_request_value($key, $to_upper = false)
    {
        if (!isset($_REQUEST[$key])) {
            return '';
        }

        $value = sanitize_text_field(wp_unslash($_REQUEST[$key]));
        return $to_upper ? strtoupper($value) : $value;
    }

    /**
     * @param string $sid_status_upper
     * @param string $order_status_lower
     * @param WC_Order $order
     * @param mixed $sid_tnxid
     * @return void
     */
    protected function handle_success_order(
        string $sid_status_upper,
        string $order_status_lower,
        WC_Order $order,
        mixed $sid_tnxid
    ): void {
        if ($sid_status_upper === self::SID_STATUS_COMPLETED) {
            if (!in_array($order_status_lower, ['completed', 'processing'])) {
                $order->add_order_note("Success payment from SiD Secure EFT (TNXID: $sid_tnxid)");
                $order->payment_complete();
                $order->reduce_order_stock();
            }

            wp_redirect(add_query_arg('utm_nooverride', '1', $this->get_return_url($order)));
        }
    }

}
