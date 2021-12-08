<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

use FernleafSystems\Integrations\Edd\Entities\CartItemVo;

class GetCartItemsFromTransactionId {

	/**
	 * @param string $sGatewayTxnId
	 * @return CartItemVo[]
	 * @throws \Exception
	 * @deprecated
	 */
	public function retrieve( $sGatewayTxnId ) {
		return ( new GetCartItemsFrom() )->transactionId( $sGatewayTxnId );
	}
}