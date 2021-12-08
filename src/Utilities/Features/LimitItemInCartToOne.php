<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Features;

class LimitItemInCartToOne {

	public function run() {
		try {
			$this->setup();
		}
		catch ( \Exception $oE ) {
			trigger_error( $oE->getMessage() );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function setup() {
		if ( !$this->verify() ) {
			throw new \Exception( 'One of the requirements to run this module is missing' );
		};

		// After an item has been added to the cart, it'll traverse all items and remove duplicates
		add_action( 'edd_pre_add_to_cart', [ $this, 'onPreAddToCart' ], 10, 1 );
		add_action( 'edd_post_add_to_cart', [ $this, 'onAddToCart' ], 10, 1 );
	}

	/**
	 * @return bool
	 */
	protected function verify() {
		return function_exists( 'get_field' )
			   && class_exists( '\FernleafSystems\Wordpress\Services\Services' );
	}

	/**
	 * If we're about to add a new download to the cart and the Download ID already exists in the cart
	 * we remove the existing item so that it will be replaced by the upcoming.
	 * @param int $nDownloadId
	 */
	public function onPreAddToCart( $nDownloadId ) {

		if ( $this->isEnabledLimitToOne( $nDownloadId ) ) {
			foreach ( edd_get_cart_contents() as $nKey => $aItem ) {
				if ( isset( $aItem[ 'id' ] ) && $nDownloadId === $aItem[ 'id' ] ) {
					EDD()->cart->remove( $nKey );
				}
			}
		}
	}

	/**
	 * After add the the onPreAddToCart, this is probably no longer required.
	 * @param int $nDownloadId
	 */
	public function onAddToCart( $nDownloadId ) {

		if ( $this->isEnabledLimitToOne( $nDownloadId ) ) {

			$bFoundAlready = false;
			$bRemoved = false;
			foreach ( EDD()->cart->contents as $nPos => $aItem ) {
				if ( $aItem[ 'id' ] == $nDownloadId ) {
					if ( !$bFoundAlready ) {
						$bFoundAlready = true;
						continue;
					}
					else {
						unset( EDD()->cart->contents[ $nPos ] );
						$bRemoved = true;
					}
				}
			}

			if ( $bRemoved ) {
				EDD()->cart->update_cart();
			}
		}
	}

	/**
	 * @param int $nDownloadId
	 * @return bool
	 */
	protected function isEnabledLimitToOne( $nDownloadId ) {
		$oDld = new \EDD_Download( $nDownloadId );
		return $oDld->ID && \get_field( 'limit_to_one', $oDld, false );
	}
}
