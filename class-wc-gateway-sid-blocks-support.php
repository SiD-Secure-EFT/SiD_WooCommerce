<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * SiD payment method integration
 *
 * @since 1.5.0
 */
final class WC_SiD_Blocks_Support extends AbstractPaymentMethodType
{
    /**
     * Name of the payment method.
     *
     * @var string
     */
    protected $name = 'sid';
    protected $settings;

    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_sid_settings', []);
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active()
    {
        $payment_gateways_class = WC()->payment_gateways();
        $payment_gateways       = $payment_gateways_class->payment_gateways();

        return $payment_gateways['sid']->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles()
    {
        $asset_path   = WC_SID_BASE_DIR . '/assets/js/index.asset.php';
        $version      = '1';
        $dependencies = [];
        if (file_exists($asset_path)) {
            $asset        = require $asset_path;
            $version      = is_array($asset) && isset($asset['version'])
                ? $asset['version']
                : $version;
            $dependencies = is_array($asset) && isset($asset['dependencies'])
                ? $asset['dependencies']
                : $dependencies;
        }
        wp_register_script(
            'wc-sid-blocks-integration',
            WP_PLUGIN_URL . WC_SID_PLUGINPATH . '/assets/js/index.js',
            $dependencies,
            $version,
            true
        );

        wp_set_script_translations(
            'wc-sid-blocks-integration',
            'woocommerce'
        );

        return ['wc-sid-blocks-integration'];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        $title       = $this->get_setting('title');
        $description = $this->get_setting('description');

        if (empty($title)) {
            $title = "SiD Secure EFT";
        }

        if (empty($description)) {
            $description = "Pay securely using online banking. No credit card required.";
        }

        return [
            'title'       => $title,
            'description' => $description,
            'supports'    => $this->get_supported_features(),
            'logo_url'    => WP_PLUGIN_URL . WC_SID_PLUGINPATH . '/assets/images/logo.png'
        ];
    }

    /**
     * Returns an array of supported features.
     *
     * @return string[]
     */
    public function get_supported_features()
    {
        $payment_gateways = WC()->payment_gateways->payment_gateways();

        return $payment_gateways['sid']->supports;
    }
}
