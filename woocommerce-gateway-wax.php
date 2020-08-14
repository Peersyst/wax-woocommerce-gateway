<?php
/*
 * Plugin Name: WooCommerce Wax Gateway
 * Plugin URI: https://wordpress.org/plugins/woocommerce-gateway-wax/
 * Description: Take WAX coin payments inn your store.
 * Author: Peersyst technologies
 * Author URI: 
 * Version:
 * Text Domain: woocommerce-gateway-wax
 * Domain Path: /languages
 *
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright 2020 Peersyst technologies
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Credits
 * Robin Pedersen - Used woocommerce-gateway-xem as boilerplate.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_WAX_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_WAX_VERSION', '2.1.9' );
define( 'WC_WAX_MIN_PHP_VER', '5.3.0' );
define( 'WC_WAX_MIN_WC_VER', '2.5.0' );
define( 'WC_WAX_MAIN_FILE', __FILE__ );

if ( ! class_exists( 'WC_Wax' ) ) {

	class WC_Wax {

		/**
		 * @var Singleton The reference the *Singleton* instance of this class
		 */
		private static $instance;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return Singleton The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @return void
		 */
		private function __clone() {}

		/**
		 * Private unserialize method to prevent unserializing of the *Singleton*
		 * instance.
		 *
		 * @return void
		 */
		private function __wakeup() {}

		/**
		 * Notices (array)
		 * @var array
		 */
		public $notices = array();


		protected function __construct() {
			add_action( 'admin_init', array( $this, 'check_environment' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			register_activation_hook( __FILE__, '\WSB\Activator::activate' );

            //Todo: Myabe add this to furture version if requested
            //add_action( 'admin_init', array( $this, 'db_setup' ) );
		}

		/**
		 * Init the plugin after plugins_loaded so environment variables are set.
		 */
		public function init() {
			if ( self::get_environment_warning() ) {
				return;
			}

			// Init the gateway itself
			$this->init_gateways();

		}

		/**
		 * Add the gateways to WooCommerce
		 *
		 * @since 1.0.0
		 */
		public function init_gateways() {

			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}

			if( class_exists( 'WC_Gateway_Wax' ) ) {
				return;
			}

			/*
			 * Include gateway class
			 * */
			include_once ( plugin_basename('includes/class-wc-gateway-wax.php'));
			include_once ( plugin_basename('includes/class-wax-ajax.php'));
			include_once ( plugin_basename('includes/class-wax-currency.php'));

			/*
			 * Need make woocommerce aware of the Gateway class
			 * */
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );

            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

            //Filter display of prices in woocommerce store based on settings.
            add_filter( 'woocommerce_get_price_html', array( $this, 'wax_change_product_price' ),10,2);
            add_filter( 'woocommerce_cart_item_price', array( $this, 'wax_change_cart_item_price' ),10,3);
			add_filter( 'woocommerce_cart_item_subtotal', array($this, 'wax_change_cart_item_total_price' ),10,3);
			add_filter( 'woocommerce_cart_subtotal', array($this, 'wax_change_cart_subtotal_price'), 10, 3);
			add_filter( 'woocommerce_cart_totals_order_total_html', array($this, 'wax_change_cart_total_price' ),10,1);

		}


		public function wax_change_cart_total_price( $value ) {
			$total = WC()->cart->total;
			$value = $this->change_price_to_wax($value, $total );
			return $value;
		}
		public function wax_change_cart_subtotal_price( $cart_subtotal, $compound, $that){
			$cart_subtotal = $this->change_price_to_wax($cart_subtotal, $that->subtotal);
			return $cart_subtotal;
		}

        public function wax_change_product_price( $price, $that ) {
	        $price = $this->change_price_to_wax($price, $that->price);
	        return $price;
        }

        public function wax_change_cart_item_price( $price, $cart_item, $cart_item_key ) {
	        $price = $this->change_price_to_wax($price, ($cart_item['line_subtotal'] + $cart_item['line_subtotal_tax']) / $cart_item['quantity']);
            return $price;
        }

		public function wax_change_cart_item_total_price( $price, $cart_item, $cart_item_key ) {
			$price = $this->change_price_to_wax($price, $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax']);
			return $price;
		}


		public function change_price_to_wax($price_string, $price){
			$currency = strtoupper( get_woocommerce_currency() ) ;
			$wax_options = get_option('woocommerce_wax_settings');
			switch ($wax_options['prices_in_wax']) {
				case "both":
					$wax_amout = round(Wax_Currency::get_wax_amount($price, $currency), 2, PHP_ROUND_HALF_UP);
					if($wax_amout){
						$new_price_string = $price_string.'&nbsp;||&nbsp;<span class="woocommerce-Price-amount amount">'.$wax_amout.'&nbsp;</span><span class="woocommerce-Price-currencySymbol">WAX</span>';
						return $new_price_string;
					}
					break;
				case "only":
					$wax_amout = round(Wax_Currency::get_wax_amount($price, $currency), 2, PHP_ROUND_HALF_UP);
					if($wax_amout){
						$new_price_string = '<span class="woocommerce-Price-amount amount">'.$wax_amout.'&nbsp;</span><span class="woocommerce-Price-currencySymbol">WAX</span>';
						return $new_price_string;
					}
					break;
				default:
					return $price_string;
			}
			return $price_string;
		}

		/**
		 * Add the gateways to WooCommerce
		 *
		 * @since 1.0.0
		 */
		public function add_gateways( $methods ) {
			$methods[] = 'WC_Gateway_Wax';
			return $methods;
		}

		/**
		 * Allow this class and other classes to add slug keyed notices (to avoid duplication)
		 */
		public function add_admin_notice( $slug, $class, $message ) {
			$this->notices[ $slug ] = array(
				'class'   => $class,
				'message' => $message
			);
		}

		/**
		 * Display any notices we've collected thus far (e.g. for connection, disconnection)
		 */
		public function admin_notices() {
			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
				echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
				echo "</p></div>";
			}
		}

		/**
		 * The backup sanity check, in case the plugin is activated in a weird way,
		 * or the environment changes after activation.
		 */
		public function check_environment() {
			$environment_warning = self::get_environment_warning();

			if ( $environment_warning && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				$this->add_admin_notice( 'bad_environment', 'error', $environment_warning );
			}
		}

		/**
		 * Checks the environment for compatibility problems.  Returns a string with the first incompatibility
		 * found or false if the environment has no problems.
		 */
		static function get_environment_warning() {
			if ( version_compare( phpversion(), WC_WAX_MIN_PHP_VER, '<' ) ) {
				$message = __( 'WooCommerce WAX - The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-wax', 'woocommerce-gateway-wax' );

				return sprintf( $message, WC_WAX_MIN_PHP_VER, phpversion() );
			}

			if ( ! defined( 'WC_VERSION' ) ) {
				return __( 'WooCommerce WAX requires WooCommerce to be activated to work.', 'woocommerce-gateway-wax' );
			}

			if ( version_compare( WC_VERSION, WC_WAX_MIN_WC_VER, '<' ) ) {
				$message = __( 'WooCommerce WAX - The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-wax', 'woocommerce-gateway-wax' );

				return sprintf( $message, WC_WAX_MIN_WC_VER, WC_VERSION );
			}

			if ( ! function_exists( 'curl_init' ) ) {
				return __( 'WooCommerce WAX - cURL is not installed.', 'woocommerce-gateway-wax' );
			}

			return false;
		}

        /**
         * Adds plugin action links
         *
         * @since 1.0.0
         */
        public function plugin_action_links( $links ) {
            $setting_link = $this->get_setting_link();

            $plugin_links = array(
                '<a href="' . $setting_link . '">' . __( 'Settings', 'woocommerce-gateway-wax' ) . '</a>'
            );
            return array_merge( $plugin_links, $links );
        }

		/**
		 * Get setting link.
		 *
		 * @since 1.0.0
		 *
		 * @return string Setting link
		 */
		public function get_setting_link() {
			$use_id_as_section = version_compare( WC()->version, '2.6', '>=' );

			$section_slug = $use_id_as_section ? 'wax' : strtolower( 'WC_Gateway_WAX' );

			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
		}

	}

	$GLOBALS['wc_wax'] = WC_Wax::get_instance();
}