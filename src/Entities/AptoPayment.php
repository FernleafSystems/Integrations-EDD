<?php

namespace FernleafSystems\Integrations\Edd\Entities;

/**
 * Class AptoPayment
 * @package FernleafSystems\Integrations\Edd\Entities
 * @property $user_id     int
 * @property $customer_id int
 * @property $status      string
 */
class AptoPayment extends \EDD_Payment {

	public function isRenewal() :bool {
		return $this->status === 'edd_subscription';
	}
}