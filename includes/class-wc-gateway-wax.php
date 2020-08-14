<?php

/**
 * Wax gateway main class based on woocommerce
 *
 * @since 1.0.0
 *
 */
class WC_Gateway_Wax extends WC_Payment_Gateway {

    /**
     * Logging enabled?
     *
     * @var bool
     */
    public $logging;


    function __construct() {
        $this->id = 'wax';
        $this->method_title = __('WAX', 'woocommerce-gateway-wax');
        $this->method_description = __('WAX works by letting clients pay WAX to your WAX wallet for orders in you shop.', 'woocommerce-gateway-wax');
        $this->has_fields = true;
        $this->icon = WC_WAX_PLUGIN_URL . ('/assets/img/pay_with_wax.svg');
        $this->order_button_text = "Waiting for payment";


        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Get setting values.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->wax_address = $this->get_option('wax_address');
        $this->match_amount = 'yes' === $this->get_option('match_amount');
        $this->logging = 'yes' === $this->get_option('logging');
        $this->prices_in_wax = $this->get_option('prices_in_wax');


        // Hooks.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        add_action('wp_enqueue_scripts', array( $this, 'payment_scripts' ));

    }



    /**
     * Payment form on checkout page
     */
    public function payment_fields() {
        $user = wp_get_current_user();
        $wax_amount = Wax_Currency::get_wax_amount($this->get_order_total(), strtoupper(get_woocommerce_currency()));

        //Todo: Lock amount for 5 minutes
        WC()->session->set('wax_amount', $wax_amount);

        if ( $user->ID ) {
            $user_email = get_user_meta($user->ID, 'billing_email', true);
            $user_email = $user_email ? $user_email : $user->user_email;
        } else {
            $user_email = '';
        }

        $wax_ref = wp_create_nonce("3h62h6u26h42h6i2462h6u4h624");

        //Start wrapper
        echo '<div id="wax-form"
			data-email="' . esc_attr($user_email) . '"
			data-amount="' . esc_attr($this->get_order_total()) . '"
			data-currency="' . esc_attr(strtolower(get_woocommerce_currency())) . '"
			data-wax-address="' . esc_attr($this->wax_address) . '"
			data-wax-amount="' . esc_attr($wax_amount) . '"
			data-wax-ref="' . esc_attr($wax_ref) . '"
			">';

        //Info box
        echo '<div id="wax-description">';
        if ( $this->description ) {
            //echo apply_filters( 'wc_swax_description', wpautop( wp_kses_post( $this->description ) ) );
        }
        echo '</div>';


        //QRcode TODO: button
        echo '<div id="wax-qr">
                <div id="wax-tx-accepted" style="font-weight: bold; text-align: center; margin-bottom: 20px; display: none;">
                    Transaction sent. Waiting to be confirmed... please DON\'T CLOSE this window.
                </div>
                <a id="wax-pay-button" class="wax-pay-button">Pay using cloud wallet</a>
            </div>';

        echo '<div id="wax-payment-desc">';

        echo '<div>';

        echo '<div class="wax-payment-desc-row">';
        echo '<label class="wax-label-for">' . __('Amount Wax:', 'woocommerce-gateway-wax') . '</label>';
        echo '<label id="wax-amount-wrapper" class="wax-label wax-amount" data-clipboard-text="' . esc_attr($wax_amount) . '">' . esc_attr($wax_amount) . '</label>';
        echo '</div>';

        echo '<div class="wax-payment-desc-row">';
        echo '<label class="wax-label-for">' . __('Address:', 'woocommerce-gateway-wax') . '</label>';
        echo '<label id="wax-address-wrapper" class="wax-label wax-address" data-clipboard-text="' . esc_attr($this->wax_address) . '">' . esc_attr($this->wax_address) . '</label>';
        echo '</div>';

        echo '<div class="wax-payment-desc-row">';
        echo '<label class="wax-label-for">' . __('Reference:', 'woocommerce-gateway-wax') . '</label>';
        echo '<label id="wax-ref-wrapper" class="wax-label wax-ref" data-clipboard-text="' . esc_attr($wax_ref) . '">' . esc_attr($wax_ref) . '</label>';
        echo '</div>';

        echo '</div>';

        echo '</div>';


        //waxProcess
        echo '<div id="wax-process"></div>';

    }

    /**
     * payment_scripts function.
     *
     * Outputs scripts used for payment
     *
     * @access public
     */
    public function payment_scripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script('woocommerce_wax_qrcode', plugins_url('assets/js/qrcode' . $suffix . '.js', WC_WAX_MAIN_FILE), array( 'jquery' ), WC_WAX_VERSION, true);
        wp_enqueue_script('waxjs', plugins_url('assets/js/waxjs' . $suffix . '.js', WC_WAX_MAIN_FILE), array( 'jquery' ), WC_WAX_VERSION, true);
        wp_enqueue_script('jquery-initialize', plugins_url('assets/js/jquery.initialize' . $suffix . '.js', WC_WAX_MAIN_FILE), array( 'jquery' ), WC_WAX_VERSION, true);
        wp_enqueue_script('clipboard', plugins_url('assets/js/clipboard' . $suffix . '.js', WC_WAX_MAIN_FILE), array( 'jquery' ), WC_WAX_VERSION, true);
        wp_enqueue_script('nanobar', plugins_url('assets/js/nanobar' . $suffix . '.js', WC_WAX_MAIN_FILE), array( 'jquery' ), WC_WAX_VERSION, true);
        wp_enqueue_script('woocommerce_wax_js', plugins_url('assets/js/wax-checkout' . $suffix . '.js', WC_WAX_MAIN_FILE), array(
            'jquery',
            'woocommerce_wax_qrcode',
            'jquery-initialize',
            'clipboard',
            'nanobar'
        ), WC_WAX_VERSION, true);
        wp_enqueue_style('woocommerce_wax_css', plugins_url('assets/css/wax-checkout.css', WC_WAX_MAIN_FILE), array(), WC_WAX_VERSION);


        //Add js variables
        $wax_params = array(
            'wc_ajax_url' => WC()->ajax_url(),
            'nounce' => wp_create_nonce("woocommerce-wax"),
            'store' => get_bloginfo()
        );

        wp_localize_script('woocommerce_wax_js', 'wc_wax_params', apply_filters('wc_wax_params', $wax_params));

    }

    public function validate_fields() {
        $wax_payment = json_decode(WC()->session->get('wax_payment'));
        if ( empty($wax_payment) ) {
            wc_add_notice(__('A WAX payment has not been registered to this checkout. Please contact our support department.', 'woocommerce-gateway-wax'), 'error');
            return false;
        }
        return true;
    }

    /**
     * Process Payment.
     *
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment( $order_id ) {

        global $woocommerce;
        $order = new WC_Order($order_id);

        //Get the WAX transaction
        $wax_payment = json_decode(WC()->session->get('wax_payment'));


        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('on-hold', __('Awaiting WAX payment', 'woocommerce'));
        update_post_meta($order_id, 'wax_payment_hash', $wax_payment->id);
        update_post_meta($order_id, 'wax_payment_ref', $wax_payment->action->data->memo);
        update_post_meta($order_id, 'wax_payment_amount', $wax_payment->amount);
        update_post_meta($order_id, 'wax_payment_fee', 0);
        update_post_meta($order_id, 'wax_payment_height', 0);
        update_post_meta($order_id, 'wax_payment_recipient', $wax_payment->action->data->to);

        // Reduce stock levels
        $order->reduce_order_stock();

        //Mark as paid
        $order->payment_complete();

        // Remove cart
        $woocommerce->cart->empty_cart();
        WC()->session->set('wax_payment', false);
        //Lock amount for 5 minutes
        WC()->session->set('wax_amount', false);


        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url()
        );
    }

    public function hex2str( $hex ) {
        $str = '';
        for ( $i = 0; $i < strlen($hex); $i += 2 ) $str .= chr(hexdec(substr($hex, $i, 2)));
        return $str;
    }

    /**
     * Get wax amount to pay
     *
     * @param float $total Amount due.
     * @param string $currency Accepted currency.
     *
     * @return float|int
     */
    public function get_wax_amount( $total, $currency = '' ) {
        if ( !$currency ) {
            $currency = get_woocommerce_currency();
        }
        /*Todo: Add filter for supported currencys. Also, could add to tri-exchange if currency outside polo currency*/
        $supported_currencys = array();

        switch ( strtoupper($currency) ) {
            // Zero decimal currencies.
            case 'BIF' :
            case 'CLP' :
            case 'DJF' :
            case 'GNF' :
            case 'JPY' :
            case 'KMF' :
            case 'KRW' :
            case 'MGA' :
            case 'PYG' :
            case 'RWF' :
            case 'VND' :
            case 'VUV' :
            case 'XAF' :
            case 'XOF' :
            case 'XPF' :
                $total = absint($total);
                break;
            default :
                $total = round($total, 2) * 100; // In cents.
                break;
        }

        return $total;
    }

    /**
     * Init settings for gateways.
     */
    public function init_settings() {
        parent::init_settings();
        $this->enabled = !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = include('wc-gateway-wax-settings.php');

        wc_enqueue_js("
			jQuery( function( $ ) {
				
			});
		");
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available() {
        if ( 'yes' === $this->enabled && $this->wax_address ) {
            return true;
        }

        return false;
    }

}
