<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class Sg_Postcode_Lookup_For_Woocommerce_Plugin_Settings {

		public function __construct() {
			add_filter( 'woocommerce_get_settings_general', array( $this, 'add_settings_to_section' ), 10, 1 );
		}

		public function add_settings_to_section( $settings ) {
			$new_settings = array();

			$new_settings[] = array(
				'id'       => 'sg_postcode_lookup_section_title',
				'title' => __( 'SG Postcode Lookup Settings', 'sg-postcode-lookup-for-woocommerce' ),
				'type'     => 'title',
			);

			$new_settings[] = array(
				'id'        => 'sg_postcode_lookup_enable_for_billing_address',
				'title'      => __( 'Enable for Billing Address', 'sg-postcode-lookup-for-woocommerce' ),
				'desc'      => __( 'Add the lookup field to the Billing Address section in checkout and account areas', 'sg-postcode-lookup-for-woocommerce' ),
				'default'   => 'yes',
				'type'      => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'sg_postcode_lookup_enable_for_shipping_address',
				'title'      => __( 'Enable for Shipping Address', 'sg-postcode-lookup-for-woocommerce' ),
				'desc'      => __( 'Add the lookup field to the Shipping Address section in checkout and account areas', 'sg-postcode-lookup-for-woocommerce' ),
				'default'   => 'yes',
				'type'      => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'sg_postcode_lookup_find_address_button_text',
				'title'      => __( 'Find Address Button Text', 'sg-postcode-lookup-for-woocommerce' ),
				'desc_tip'      => __( 'Change the text on the Find Address buttons. If left blank, translations will work for "Find Address".', 'sg-postcode-lookup-for-woocommerce' ),
				'placeholder' => __( 'Find Address', 'sg-postcode-lookup-for-woocommerce' ),
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'sg_postcode_lookup_find_address_searching_text',
				'title'      => __( 'Find Address Searching Text', 'sg-postcode-lookup-for-woocommerce' ),
				'desc_tip'      => __( 'Change the text shown on the button when a search is in progress. If left blank, translations will work for "Searching...".', 'sg-postcode-lookup-for-woocommerce' ),
				'placeholder' => __( 'Searching...', 'sg-postcode-lookup-for-woocommerce' ),
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'sg_postcode_lookup_for_woocommerce_options',
				'type'      => 'sectionend',
			);

			$settings = array_merge( $settings, $new_settings );

			return $settings;
		}

	}

	new Sg_Postcode_Lookup_For_Woocommerce_Plugin_Settings();