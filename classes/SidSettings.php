<?php

class SidSettings
{
    /**
     * Get all SiD Secure EFT settings
     *
     * @return array
     */
    public static function get_settings()
    {
        return array(
            'enabled'       => array(
                'title'   => __('Enable/Disable', 'sid_woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable SiD Secure EFT', 'sid_woocommerce'),
                'default' => 'yes',
            ),
            'title'         => array(
                'title'       => __('Title', 'sid_woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'sid_woocommerce'),
                'default'     => __('SiD Secure EFT', 'sid_woocommerce'),
                'desc_tip'    => true,
            ),
            'description'   => array(
                'title'   => __('Description', 'sid_woocommerce'),
                'type'    => 'textarea',
                'default' => __('Pay securely using online banking. No credit card required.', 'sid_woocommerce'),
            ),
            'merchant_code' => array(
                'title'       => __('Merchant Code', 'sid_woocommerce'),
                'type'        => 'text',
                'description' => __('Your SiD Secure EFT merchant code', 'sid_woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'username'      => array(
                'title'       => __('API Username', 'sid_woocommerce'),
                'type'        => 'text',
                'description' => __('Your SiD Secure EFT order query username', 'sid_woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'password'      => array(
                'title'       => __('API Password', 'sid_woocommerce'),
                'type'        => 'password',
                'description' => __('Your SiD Secure EFT order query password', 'sid_woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'private_key'   => array(
                'title'       => __('Private Key', 'sid_woocommerce'),
                'type'        => 'password',
                'description' => __('Your SiD Secure EFT private key', 'sid_woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            )
        );
    }
}
