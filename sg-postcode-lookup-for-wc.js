(function(jQuery) {

  var postcode_lookup_cache = [];

  jQuery('#billing_postcode').on('keyup', function(e) {
    trigger_auto_complete(e, 'billing');
  });

  jQuery('#shipping_postcode').on('keyup', function(e) {
    trigger_auto_complete(e, 'shipping');
  });

  function trigger_auto_complete(e, address_type) {
    if (String.fromCharCode(e.keyCode).match(/[^0-9]/g)) return false;

    var postcode = e.target.value.replace(/[^0-9]/g, "");
    if (/^\d{6}$/.test(postcode)) {
        do_postcode_lookup( postcode, address_type, document.getElementById(address_type + '_sg_postcode_lookup_button_field_button') );
    }
  }

  function lookup_button_clicked( btn ) {
      var address_type = '';
      if ( btn.id.indexOf('billing_') > -1 ) {
          address_type = 'billing';
      } else if( btn.id.indexOf('shipping_') > -1 ) {
          address_type = 'shipping';
      }

      if ( address_type ) {
          var postcode_field = document.getElementById( address_type + '_postcode' );
          var postcode = postcode_field.value.replace(/[^0-9]/g, "");

          if ( postcode.length > 0 ) {
              do_postcode_lookup( postcode, address_type, btn );
          }
      }
  }

  function do_postcode_lookup( postcode, address_type, btn ) {
      if ( typeof postcode_lookup_cache[ postcode + '_' + address_type ] != 'undefined' ) {
          show_address_selector( postcode_lookup_cache[ postcode + '_' + address_type ] );
          return;
      }

      var ajax_data;
      ajax_data = "action=sg_postcode_lookup_wc";
      ajax_data += "&postcode=" + postcode;
      ajax_data += "&address_type=" + address_type;

      if ( XMLHttpRequest ) {
          if ( btn ) {
              btn.innerText = sg_postcode_lookup_for_wc.searching_text;
              btn.disabled = true;
          }
          var xhr = new XMLHttpRequest();

          xhr.open('POST', sg_postcode_lookup_for_wc.ajax_url );
          xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
          xhr.onload = function() {
              if ( btn ) {
                  btn.innerText = sg_postcode_lookup_for_wc.button_text;
                  btn.disabled = false;
              }

              var response = JSON.parse( xhr.responseText );
              if ( !response.error_code ) {
                  postcode_lookup_cache[ postcode + '_' + address_type ] = response;
                  tidy_postcode_field( response );
                  show_address_selector( response );
              } else {
                  alert( response.error );
              }
          };
          xhr.onerror = function () {
              if ( btn ) {
                  btn.innerText = sg_postcode_lookup_for_wc.button_text;
                  btn.disabled = false;
              }
          };
          xhr.send( ajax_data );
      }
  }

  function tidy_postcode_field( r ) {
      var postcode_field = document.getElementById( r.address_type + '_postcode' );
      if ( postcode_field && r.postcode ) {
          postcode_field.value = r.postcode;
      }
  }

  function show_address_selector( r ) {
      var selector_form_row_id = r.address_type + '_sg-postcode-lookup-for-woocommerce-address-selector';
      var selector_form_row = document.getElementById( selector_form_row_id );

      if ( selector_form_row ) {
          selector_form_row.parentNode.removeChild(selector_form_row);
      }
      
      if ( r.address_count > 0 ) {
          var button_form_row = document.getElementById( r.address_type + '_sg_postcode_lookup_button_field' );
          button_form_row.insertAdjacentHTML( 'afterend', r.fragment.trim() );

          if (r.address_count == 1) {
            selectOption(1, r);
            jQuery('#' + selector_form_row_id).hide();
          } else {
            jQuery('#' + selector_form_row_id).show();
          }
      } else {
        do_address_change( selector_form_row_id, '||||' );
      }
  }

  function selectOption(optionIndex, r) {
    var selector_form_row_id = r.address_type + '_sg-postcode-lookup-for-woocommerce-address-selector-select';
    var selector_form_row_element = document.getElementById( selector_form_row_id );

    if (selector_form_row_element) {
        var select = jQuery(selector_form_row_element);
        if (select.children().length >= optionIndex) {
            let value = select.find('option:eq(' + optionIndex + ')').val();
            select.val(value).change();
        }
    }
  }

  function do_address_change( src_id, address ) {
      var address_type = 'billing';
      if ( src_id.indexOf('shipping_') > -1 ) address_type = 'shipping';

      var address_parts = address.split('|');
      var address_field_ids = [ 'address_1', 'address_2', 'city', 'state' ];
      var field_id, field_element;
      for (var i = 0; i < address_field_ids.length; i++) {
          field_id = address_type + '_' + address_field_ids[ i ];
          field_element = document.getElementById( field_id );
          if ( field_element !== null ) {
              field_element.value = address_parts[ i ];
          }
      }

      // trigger WooCommerce's Ajax order update so we get shipping methods updated etc.
      jQuery( document.body ).trigger( 'update_checkout' );
  }

  var lookup_buttons = document.getElementsByClassName('sg-postcode-lookup-button');
  if ( lookup_buttons.length > 0 ) {
      for(var i = 0; i < lookup_buttons.length; i++) {
          lookup_buttons[i].addEventListener("click", function(e) {
              lookup_button_clicked( this );
          });
      }
  }

  // add event listeners for the selectors
  jQuery( '.woocommerce-address-fields, .woocommerce-billing-fields, .woocommerce-shipping-fields' ).on( 'change', 'select', function (e) {
      if ( 'billing_sg-postcode-lookup-for-woocommerce-address-selector-select' == e.target.id || 'shipping_sg-postcode-lookup-for-woocommerce-address-selector-select' == e.target.id ) {
          var val = e.target.options[e.target.selectedIndex].value;
          if ( '' == val ) {
              val = '||||';
          }
          do_address_change( e.target.id, val );
      }
  } );

  // if we're on the WC checkout, add a clearfix to the additional fields wrappter
  if ( sg_postcode_lookup_for_wc.clear_additional_fields ) {
      var additional_fields_wrappers = document.getElementsByClassName('woocommerce-additional-fields__field-wrapper');
      if ( additional_fields_wrappers.length > 0 ) {
          for(var i = 0; i < additional_fields_wrappers.length; i++) {
              additional_fields_wrappers[i].style.clear = 'both';
          }
      }
  }

})(jQuery);