# SiD_WooCommerce

## SiD Secure EFT plugin v1.2.0 for WooCommerce v9.9

This is the SiD Secure EFT plugin for WooCommerce. Please feel free to contact the Payfast support team at
support@payfast.io should you require any assistance.

## Installation

1. **Download the Plugin**

    - Visit the [releases page](https://github.com/SiD-Secure-EFT/SiD_WooCommerce/releases) and
      download [sid_woocommerce.zip](https://github.com/SiD-Secure-EFT/SiD_WooCommerce/releases/download/v1.2.0/sid_woocommerce.zip).

2. **Install the Plugin**

    - Log in to your WordPress Admin panel.
    - Navigate to **Plugins > Add New > Upload Plugin**.
    - Click **Choose File** and select `sid_woocommerce.zip`.
    - Click **Install Now**.
    - Click **Activate Plugin**.

3. **Configure the Plugin**

    - Navigate to **WooCommerce > Settings**.
        - Go to the **Payments** tab.
        - Select **SiD Secure EFT** from the list of payment methods.
        - Tick the **Enable SiD Secure EFT** checkbox.
        - Configure the plugin by entering your SiD Secure EFT credentials and preferences.
        - Set both the **Buyer Return URL** and **Merchant Notification URL** to the same URL in the format:  
          `https://DOMAIN.NAME?wc-api=WC_Gateway_SID`
        - Request this configuration by emailing [support@payfast.io](mailto:support@payfast.io) or through the
          **Account Settings** tab in the SiD Merchant Portal.

## Collaboration

Please submit pull requests with any tweaks, features or fixes you would like to share.
