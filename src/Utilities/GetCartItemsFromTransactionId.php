<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

use FernleafSystems\Integrations\Edd\Entities\CartItemVo;

class GetCartItemsFromTransactionId {

	/**
	 * @param string $taxID
	 * @return CartItemVo[]
	 * @throws \Exception
	 * @deprecated
	 */
	public function retrieve( $taxID ) {
		return ( new GetCartItemsFrom() )->transactionId( $taxID );
	}
}