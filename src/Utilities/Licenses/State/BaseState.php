<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\State;

use FernleafSystems\Integrations\Edd\Utilities\Licenses\BaseLicenses;
use FernleafSystems\Utilities\{
	Data\Adapter\StdClassAdapter,
	Logic\OneTimeExecute
};

/**
 * Class BaseLicenses
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
abstract class BaseState extends BaseLicenses {

	use OneTimeExecute;
	use StdClassAdapter {
		__get as __adapterGet;
	}

	/**
	 * @param string $sProperty
	 * @return mixed
	 */
	public function __get( $sProperty ) {
		$this->execute();
		return $this->__adapterGet( $sProperty );
	}
}