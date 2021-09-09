<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation\PaypalFreeagent;

use EDD\Gateways\PayPal\API;
use FernleafSystems\Integrations\Edd\Consumers\EddPaymentConsumer;
use FernleafSystems\Integrations\Edd\Utilities\GetSubscriptionsFromPaymentId;
use FernleafSystems\Integrations\Edd\Utilities\GetTransactionIdsFromPayment;
use FernleafSystems\Integrations\Freeagent\Service\PayPal\TransactionVO;
use FernleafSystems\Wordpress\Services\Services;

class GetPaypalTransactionsFromPayment {

	use EddPaymentConsumer;

	public function retrieve( int $expected = -1 ) :TransactionVO {
		$p = $this->getEddPayment();
		$carb = Services::Request()->carbon( true );
		$carb->setTimeFromTimeString( $p->date );

		if ( !class_exists( '\EDD\Gateways\PayPal\API' ) ) {
			throw new \Exception( "EDD PayPal Commerce API isn't available" );
		}

		$api = new API();
		$response = $api->make_request(
			add_query_arg( [
				'start_time' => date( 'Y-m-d\TH:i:s.Z\Z', $carb->subDays( 1 )->timestamp ),
				'end_time'   => date( 'Y-m-d\TH:i:s.Z\Z', $carb->addDays( 2 )->timestamp ),
			], sprintf( '%s/v1/billing/subscriptions/%s/transactions', $api->api_url, $this->getPaypalSubscriptionProfileID() ) ),
			[], [], 'GET'
		);
		if ( empty( $response ) || !isset( $response->transactions ) || !is_array( $response->transactions ) ) {
			throw new \Exception( 'Empty response' );
		}
		if ( $expected >= 0 && count( $response->transactions ) !== $expected ) {
			throw new \Exception( sprintf( "Number of transactions (%s) doesn't align with expected (%s).",
				count( $response->transactions ), $expected ) );
		}
		if ( empty( $response->transactions ) ) {
			throw new \Exception( 'No transactions' );
		}

		$txns = ( new GetTransactionIdsFromPayment() )->retrieve( $p );
		$txnID = array_pop( $txns );

		$theTxn = null;
		foreach ( $response->transactions as $transaction ) {
			if ( $transaction->id === $txnID ) {
				$theTxn = ( new TransactionVO() )->applyFromArray(
					json_decode( json_encode( $transaction ), true )
				);
				break;
			}
		}

		if ( empty( $theTxn ) ) {
			throw new \Exception( sprintf( "Couldn't find transaction (%s) wihthin API results.", $txnID ) );
		}
		return $theTxn;
	}

	private function getPaypalSubscriptionProfileID() :string {
		$paymentWithSubscription = $this->getEddPayment();
		if ( !empty( $paymentWithSubscription->parent_payment ) ) {
			$paymentWithSubscription = edd_get_payment( $paymentWithSubscription->parent_payment );
		}

		$subs = ( new GetSubscriptionsFromPaymentId() )->retrieve( $paymentWithSubscription->ID );
		if ( empty( $subs ) ) {
			throw new \Exception( 'No subscription could be found for payment: '.$paymentWithSubscription->ID );
		}
		return $subs[ 0 ]->profile_id;
	}
}