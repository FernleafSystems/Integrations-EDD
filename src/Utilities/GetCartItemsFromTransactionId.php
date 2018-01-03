<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

use FernleafSystems\Integrations\Edd\Entities\CartItemVo;

/**
 * Class GetCartItemsFromTransactionId
 * @package FernleafSystems\WordPress\Integrations\Edd\Utilities
 */
class GetCartItemsFromTransactionId {

	/**
	 * @deprecated
	 * @param string $sGatewayTxnId
	 * @return CartItemVo[]
	 * @throws \Exception
	 */
	public function retrieve( $sGatewayTxnId ) {
		return ( new GetCartItemsFrom() )->transactionId( $sGatewayTxnId );
	}
}