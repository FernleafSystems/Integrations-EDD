<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Features;

/**
 * Class DynamicLicenseLimits
 * @package FernleafSystems\Integrations\Edd\Utilities\Features
 */
class DynamicLicenseLimits {

	public function run() {
		$this->setup();
	}

	public function setup() {
		if ( !$this->verify() ) {
			throw new \Exception( 'One of the requirements to run this module is missing' );
		};

		add_action( 'wp_footer', [ $this, 'printJsHandler' ] );
		add_action( 'edd_checkout_table_header_first', [ $this, 'printLicenseLimitsCheckoutHeader' ] );
		add_action( 'edd_checkout_table_body_first', [ $this, 'printLicenseLimitsQuantitiesInput' ] );

		// add the default dynamic license limit (1) when item is added to cart
		add_filter( 'edd_add_to_cart_item', [ $this, 'filterAddToCart' ] );
		// adjust the item price to take account the chosen dynamic license limit
		add_filter( 'edd_cart_item_price', [ $this, 'filterCartItemPrice' ], 100, 3 );

		add_action( 'wp_ajax_update_dlq', [ $this, 'ajax_update_cart_item_dynamic_license_limits' ] );
		add_action( 'wp_ajax_nopriv_update_dlq', [ $this, 'ajax_update_cart_item_dynamic_license_limits' ] );

		// Dynamically adjust the newly stored license with the chosen limit.
		add_action( 'edd_sl_store_license', [ $this, 'adjustDynamicLicenses' ], 20, 4 );
	}

	/**
	 * @return bool
	 */
	protected function verify() {
		return function_exists( 'edd_software_licensing' )
			   && function_exists( 'get_field' )
			   && class_exists( '\FernleafSystems\Wordpress\Services\Services' );
	}

	/**
	 * @param int    $nLicenseId
	 * @param int    $nDownloadId
	 * @param int    $nPaymentId
	 * @param string $sType bundle/default
	 */
	public function adjustDynamicLicenses( $nLicenseId, $nDownloadId, $nPaymentId, $sType ) {
		$aCartDetails = edd_get_payment_meta_cart_details( $nPaymentId );

		/** @var \EDD_SL_License[] $aAllLicenses */
		$aAllLicenses = edd_software_licensing()->get_licenses_of_purchase( $nPaymentId );
		foreach ( $aAllLicenses as $oLicense ) {

			if ( $oLicense->ID == $nLicenseId ) {
				foreach ( $aCartDetails as $aCartItem ) {
					if ( $nDownloadId == $aCartItem[ 'id' ] ) {
						$aOptions = $aCartItem[ 'item_number' ][ 'options' ];
						if ( isset( $aOptions[ 'dynamic_license_limit' ] ) ) {
							$nLimit = (int)$aOptions[ 'dynamic_license_limit' ];

							if ( $nLimit > 0 && $nLimit != $oLicense->license_limit() ) {
								$oLicense->update_meta( 'activation_limit', $nLimit );
								( new \EDD_Payment( $nPaymentId ) )
									->add_note( 'License Activation Limit automatically adjusted to '.$nLimit );
							}
						}
						break( 2 );
					}
				}
			}
		}
	}

	public function printJsHandler() {
		echo '
		<script type="text/javascript">

			jQuery(document.body).on("change", ".edd-item-dynamic_license_quantity", function(){
				var $oThis = jQuery( this );
				$oThis.prop( "disabled", true );
				var nValue = $oThis.val();
				nValue = nValue.replace( /[^0-9]/g, "" );
				if ( nValue.length === 0 || nValue === "0" ) {
					nValue = 1;
				}
				$oThis.val( nValue );
				
				var postData = {
					action: "update_dlq",
					quantity: nValue,
					download_id: $oThis.data("dld-id")
				};
				
				jQuery.post( edd_global_vars.ajaxurl, postData,
					function ( oResponse ) { }
				)
				.always( function () {
					recalculate_taxes();
					$oThis.prop( "disabled", false );
				} );
		} );
		</script>';
	}

	/**
	 * @param float $nPrice
	 * @param int   $nDownloadId
	 * @param array $aOptions
	 * @return float
	 */
	public function filterCartItemPrice( $nPrice, $nDownloadId, $aOptions ) {
		if ( $this->isEnabledDynamicLicenseLimits( $nDownloadId ) ) {
			$nDynQuantity = isset( $aOptions[ 'dynamic_license_limit' ] ) ? $aOptions[ 'dynamic_license_limit' ] : 1;
			$nPrice *= $nDynQuantity;
		}
		return $nPrice;
	}

	public function ajax_update_cart_item_dynamic_license_limits() {
		$nQuantity = (int)\FernleafSystems\Wordpress\Services\Services::Request()->post( 'quantity' );
		$nDownloadId = (int)\FernleafSystems\Wordpress\Services\Services::Request()->post( 'download_id' );

		if ( $this->isEnabledDynamicLicenseLimits( $nDownloadId ) ) {
			$nItemPos = EDD()->cart->get_item_position( $nDownloadId );
			if ( $nItemPos !== false ) {
				if ( $nQuantity < 1 ) {
					$nQuantity = 1;
				}
				EDD()->cart->contents[ $nItemPos ][ 'options' ][ 'dynamic_license_limit' ] = $nQuantity;
				EDD()->cart->update_cart();
			}
		}
	}

	public function filterAddToCart( $aCartItem ) {

		if ( is_array( $aCartItem ) && !empty( $aCartItem[ 'id' ] ) ) {
			if ( $this->isEnabledDynamicLicenseLimits( $aCartItem[ 'id' ] ) ) {
				if ( !isset( $aCartItem[ 'options' ] ) ) {
					$aCartItem[ 'options' ] = [];
				}
				$aCartItem[ 'options' ][ 'dynamic_license_limit' ] = 1;
			}
		}
		return $aCartItem;
	}

	public function printLicenseLimitsCheckoutHeader() {
		echo '<th class="edd_cart_extras">How many licenses?</th>';
	}

	/**
	 * @param array $aItem
	 */
	public function printLicenseLimitsQuantitiesInput( $aItem ) {
		echo '<td>';

		if ( $this->isEnabledDynamicLicenseLimits( $aItem[ 'id' ] ) ) {
			$nLicQuantity = $aItem[ 'options' ][ 'dynamic_license_limit' ];
			echo '<input type="text" min="1" step="1"
			name="edd-cart-download-dynamic_license_quantity"
			data-dld-id="'.$aItem[ 'id' ].'" style="max-width: 110px;"
			data-key="dynamic_license_quanity" class="edd-input edd-item-dynamic_license_quantity"
			value="'.$nLicQuantity.'" />';
		}
		else {
			echo 'Not Available';
		}

		echo '</td>';
	}

	/**
	 * @param int $nDownloadId
	 * @return bool
	 */
	protected function isEnabledDynamicLicenseLimits( $nDownloadId ) {
		$oDld = new \EDD_Download( $nDownloadId );
		return $oDld->ID && get_field( 'enable_dynamic_licenses', $oDld, false );
	}
}
