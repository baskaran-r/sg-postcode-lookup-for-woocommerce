<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class Sg_Postcode_Lookup_For_Woocommerce_Plugin_Front {

		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js' ) );
			$this->init_checkout_fields();

			add_action( 'wp_ajax_sg_postcode_lookup_wc', array( $this, 'do_postcode_lookup' ) );
			add_action( 'wp_ajax_nopriv_sg_postcode_lookup_wc', array( $this, 'do_postcode_lookup' ) );
		}

		public function do_postcode_lookup() {
			$output = array();

			if ( !empty( $_POST['postcode'] ) ) {
				// sanitize postcode
				$postcode = strtoupper( preg_replace("/[^0-9]/i", "", $_POST['postcode'] ) );

				if ( !empty( $postcode ) ) {
					$url = "https://developers.onemap.sg/commonapi/search?searchVal=" . rawurlencode( $postcode ) . "&returnGeom=N&getAddrDetails=Y";

					$result = wp_remote_request( $url );

					switch( intval( $result['response']['code'] ) ) {
						case 200:
							$address_type = 'billing';
							if ( 'shipping' == $_POST['address_type'] ) $address_type = 'shipping';
							$addresses = array();
							$array = json_decode( $result['body'] );
							foreach( $array->results as $address ) {
								$building = $address->BUILDING;
								if ($address->BUILDING == 'NIL') {
									$building = "";
								}
								$this_address = array();
								$address_lines = array(
									$address_type . '_address_1' => $address->BLK_NO . ", " .$address->ROAD_NAME,
									$address_type . '_address_2' => "",
									$address_type . '_city' => $building,
									$address_type . '_state' => $building,
								);
								$this_address['option'] = implode( "|", array_values( $address_lines ) );
								$this_address['label'] = str_replace("|", ", ", preg_replace( "/\|+/", "|", $this_address['option'] ) );

								$addresses[] = $this_address;
							}

							$fragment = $this->get_address_selector_html( $addresses, $address_type );
							$output = array(
								'postcode' => $array->postcode ?: $postcode,
								'address_count' => count( $addresses ),
								'address_type' => $address_type,
								'fragment' => $fragment,
							);
							break;

						default:
							$output = array(
								'error' =>__('Server error. Please try again later.', 'sg-postcode-lookup-for-woocommerce' ),
								'error_code' => $result['response']['code'],
							);
							break;
					}
				}
			}
			if ( empty( $output ) ) {
				$output = array(
					'error' =>__('No postcode was supplied.', 'sg-postcode-lookup-for-woocommerce' ),
					'error_code' => 400,
				);
			}
			if ( !empty( $output['error_code'] ) ) {
				$output['error'] = apply_filters( 'sg-postcode-lookup-for-woocommerce_api_error_' . $output['error_code'], $output['error'] );
			}
			wp_die( json_encode( $output ) );
		}

		public function get_address_selector_html( $addresses, $address_type ) {
			$p_id = $address_type . '_sg-postcode-lookup-for-woocommerce-address-selector';
			$p_class = apply_filters( 'sg-postcode-lookup-for-woocommerce_' . $address_type . '_selector_row_class', 'form-row form-row-wide' );
			$select_id = $address_type . '_sg-postcode-lookup-for-woocommerce-address-selector-select';

			$html = '<p class="' . esc_attr( $p_class ) . '" id="' . esc_attr( $p_id ) . '">';
			$html.= '<label for="' . esc_attr( $address_type ) . '_sg-postcode-lookup-for-woocommerce-address-selector-select">' . __( 'Select your address to populate the form', 'sg-postcode-lookup-for-woocommerce' ) . '</label>';
			$html.= '<span class="woocommerce-input-wrapper"><select id="' . esc_attr( $select_id ) . '">';
			$html.= '<option value="">' . esc_html( sprintf( _n( '%s address found', '%s addresses found', count( $addresses ), 'sg-postcode-lookup-for-woocommerce' ), number_format_i18n( count( $addresses ) ) ) ) . '</option>';

			foreach( $addresses as $address ) {
				$html.= '<option value="' . esc_attr( $address['option'] ) . '">' . esc_html( $address['label'] ) . '</option>';
			}
			$html.= '</select></span>';
			$html.= '</p>';
			return $html;
		}

		public function enqueue_js() {
			// only enqueue on checkout or account pages
			if ( is_checkout() || is_account_page() || is_edit_account_page() ) {
				wp_register_script( 'sg_postcode_lookup_for_wc', SG_POSTCODE_LOOKUP_PLUGIN_URL . 'sg-postcode-lookup-for-wc.min.js', array( 'jquery' ), '1.0', true );
				wp_enqueue_script( 'sg_postcode_lookup_for_wc' );

				$options = array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'clear_additional_fields' => apply_filters( 'sg-postcode-lookup-for-woocommerce_clear_additional_fields', true ),
					'button_text' => self::get_find_button_text(),
					'searching_text' => self::get_searching_text(),
				);
				wp_localize_script( 'sg_postcode_lookup_for_wc', 'sg_postcode_lookup_for_wc', $options );
			}
		}

		public function init_checkout_fields() {
			if ( 'no' != get_option( 'sg_postcode_lookup_enable_for_billing_address' ) || 'no' != get_option( 'sg_postcode_lookup_enable_for_shipping_address' ) ) {
				add_filter( 'woocommerce_get_country_locale_default', array( $this, 'modify_country_locale_default' ), 10, 1 );
				add_filter( 'woocommerce_get_country_locale', array( $this, 'modify_country_locale' ), 10, 2 );
				add_filter( 'woocommerce_country_locale_field_selectors', array( $this, 'modify_country_locale_field_selectors' ), 10, 1 );
				add_filter( 'woocommerce_form_field_sg_postcode_lookup_button', array( $this, 'render_postcode_lookup_button' ), 10, 4 );
			}

			add_filter( 'woocommerce_default_address_fields', array( $this, 'modify_default_fields' ), 10 );

			if ( 'no' != get_option( 'sg_postcode_lookup_enable_for_billing_address' ) ) {
				add_filter( 'woocommerce_billing_fields', array( $this, 'modify_billing_fields' ), 10 );
			}

			if ( 'no' != get_option( 'sg_postcode_lookup_enable_for_shipping_address' ) ) {
				add_filter( 'woocommerce_shipping_fields', array( $this, 'modify_shipping_fields' ), 10, 1 );
			}
		}

		public function modify_country_locale_default( $locale ) {
			$locale['sg_postcode_lookup_button']['hidden'] = false;
			return $locale;
		}

		public function modify_country_locale( $locale ) {
				$locale['GB']['postcode']['priority'] = 45;
				$locale['GB']['sg_postcode_lookup_button']['priority'] = 46;
				$locale['GB']['sg_postcode_lookup_button']['hidden'] = false;
			return $locale;
		}

		public function modify_country_locale_field_selectors( $locale_fields ) {
			$locale_fields['sg_postcode_lookup_button'] = "#billing_sg_postcode_lookup_button_field, #shipping_sg_postcode_lookup_button_field";
			return $locale_fields;
		}

		public function modify_default_fields( $fields ) {
			return $this->modify_fields( $fields, '' );
		}

		public function modify_billing_fields( $fields ) {
			return $this->modify_fields( $fields, 'billing' );
		}

		public function modify_shipping_fields( $fields ) {
			return $this->modify_fields( $fields, 'shipping' );
		}

		private function modify_fields( $fields, $type = 'billing' ) {
			if ( !empty( $type ) ) {
				$type .= '_';
			}

			// move postcode to after country
			$country_priority = $fields[ $type . 'country']['priority'];
			$fields[ $type . 'postcode']['priority'] = $country_priority + 5;

			// change postcode so it's a form-row-first jobber
			if ( !empty( $fields[ $type . 'postcode']['class'] ) ) {
				if ( !is_array( $fields[ $type . 'postcode']['class'] ) ) {
					$fields[ $type . 'postcode']['class'] = array( $fields[ $type . 'postcode']['class'] );
				}
			} else {
				$fields[ $type . 'postcode']['class'] = array();
			}
			$fields[ $type . 'postcode']['class'][] = 'form-row-first';

			// remove form-row-wide if it's in there
			if ( false !== ( $wide_key = array_search( 'form-row-wide', $fields[ $type . 'postcode']['class'] ) ) ) {
				unset( $fields[ $type . 'postcode']['class'][ $wide_key ] );
				$fields[ $type . 'postcode']['class'] = array_values( $fields[ $type . 'postcode']['class'] );
			}

			// add postcode lookup button
			$fields[ $type . 'sg_postcode_lookup_button'] = array(
				'type' => 'sg_postcode_lookup_button',
				'label' => self::get_find_button_text(),
				'class' => array(
					'form-row-last',
				),
				'priority' => $country_priority + 7,
			);

			return $fields;
		}

		public function render_postcode_lookup_button( $field, $key, $args, $value ) {
			$priority = ( !empty( $args['priority'] ) ) ? $args['priority'] : '';
			$class = ( !empty( $args['class'] ) ) ? esc_attr( implode( ' ', $args['class'] ) ) : '';
			$id    = ( !empty( $args['id'] ) ) ? esc_attr( $args['id'] ) . '_field' : '';

			// note: this render code is in a <script> tag so that it does not appear if JS is disabled for any reason
			ob_start();
			?>
			<script>
				document.write( '<p class="form-row <?php echo $class; ?>" id="<?php echo $id; ?>" data-priority="<?php echo esc_attr( $priority ); ?>"><br>' );
				document.write( '<button type="button" class="button alt sg-postcode-lookup-button" id="<?php echo $id;?>_button"><?php echo esc_html( $args['label'] ); ?></button></p>' );
			</script>
			<?php
			return ob_get_clean();
		}

		/**
		 * @return string
		 */
		private static function get_find_button_text() {
			$button_text = __( 'Find Address', 'sg-postcode-lookup-for-woocommerce' );
			if ( !empty( get_option( 'sg_postcode_lookup_find_address_button_text' ) ) ) {
				$button_text = get_option( 'sg_postcode_lookup_find_address_button_text' );
			}

			return apply_filters( 'sg-postcode-lookup-for-woocommerce_find-address-button-text', $button_text );
		}

		/**
		 * @return string
		 */
		private static function get_searching_text() {
			$searching_text = __( 'Searching...', 'sg-postcode-lookup-for-woocommerce' );
			if ( !empty( get_option( 'sg_postcode_lookup_find_address_searching_text' ) ) ) {
				$searching_text = get_option( 'sg_postcode_lookup_find_address_searching_text' );
			}

			return apply_filters( 'sg-postcode-lookup-for-woocommerce_find-address-searching-text', $searching_text );
		}

	}

	new Sg_Postcode_Lookup_For_Woocommerce_Plugin_Front();