<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation\PaypalFreeagent;

use FernleafSystems\Integrations\Edd\Accounting\Reconciliation\CommonEddBridge;
use FernleafSystems\Integrations\Paypal_Freeagent\Reconciliation\Bridge\PaypalBridge;

class PaypalEddBridge implements PaypalBridge {

	use CommonEddBridge;
}