<?php
/*
 * Plugin Name: SG Postcode Lookup for Woocommerce
 * Plugin URI: https://github.com/baskaranrb/sg-postcode-lookup-for-woocommerce
 * Version: 1.0.0
 * Author: Baskaran
 * Author URI: https://baskar.io
 * Description: Adds a SG postcode address lookup tool to the WooCommerce checkout process.
 * Tested up to: 5.7
 * WC requires at least: 5.3.0
 * WC tested up to: 5.3.0
 * Text Domain: sg-postcode-lookup-for-woocommerce
 * Domain Path: /lang
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	define('SG_POSTCODE_LOOKUP_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR );
	define('SG_POSTCODE_LOOKUP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

	class Sg_Postcode_Lookup_For_Woocommerce_Plugin {

		function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_languages' ) );
			add_action( 'plugins_loaded', array( $this, 'load_class' ), 15 );

			add_action( 'admin_init', array( $this, 'init_plugin' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
		}

		function load_class() {
			require SG_POSTCODE_LOOKUP_DIR . 'class.settings.php';
			require SG_POSTCODE_LOOKUP_DIR . 'class.checkout.php';
		}

		function load_languages() {
			load_plugin_textdomain( 'sg-postcode-lookup-for-woocommerce', false, SG_POSTCODE_LOOKUP_DIR . 'lang' . DIRECTORY_SEPARATOR );
		}

		/**
		 * Check if WooCommerce is active - if not, then deactivate this plugin and show a suitable error message
		 */
		function init_plugin(){
		    if ( is_admin() ) {
		        if ( !class_exists( 'WooCommerce' ) ) {
		            add_action( 'admin_notices', array( $this, 'woocommerce_deactivated_notice' ) );
		            deactivate_plugins( plugin_basename( __FILE__ ) );
		        }
		    }
		}

		function woocommerce_deactivated_notice() {
		    ?>
		    <div class="notice notice-error"><p><?php esc_html_e( 'SG Postcode Lookup for Woocommerce plugin requires WooCommerce to be installed and activated.', 'sg-postcode-lookup-for-woocommerce' ) ?></p></div>
		    <?php
		}

		function add_settings_link( $links ) {
			if ( !is_array( $links ) ) {
				$links = array();
			}
			$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=general' ) . '#sg_postcode_lookup_enable_for_billing_address">' . __( 'Settings', 'sg-postcode-lookup-for-woocommerce' ) . '</a>';
			return $links;
		}

	}

	new Sg_Postcode_Lookup_For_Woocommerce_Plugin();
