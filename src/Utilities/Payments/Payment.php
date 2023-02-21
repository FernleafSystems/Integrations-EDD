<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Utilities\Payments;

class Payment {

	public const STATUS_ABANDONED = 'abandoned';
	public const STATUS_FAILED = 'failed';
	public const STATUS_NORMAL_ORDER = 'complete';
	public const STATUS_NORMAL_ORDER_LEGACY = 'publish';
	public const STATUS_PENDING = 'pending';
	public const STATUS_RENEWAL = 'edd_subscription';
	public const STATUS_REFUND_FULL = 'refunded';
	public const STATUS_REFUND_PARTIAL = 'partially_refunded';
	public const STATUS_REVOKED = 'revoked';

	public static function IsCompleted( \EDD_Payment $p ) :bool {
		return self::IsNormalOrder( $p ) || self::IsRenewal( $p );
	}

	public static function IsNormalOrder( \EDD_Payment $p ) :bool {
		return self::IsStatus( $p, self::STATUS_NORMAL_ORDER ) || self::IsStatus( $p, self::STATUS_NORMAL_ORDER_LEGACY );
	}

	public static function IsRefunded( \EDD_Payment $p ) :bool {
		return self::IsStatus( $p, self::STATUS_REFUND_PARTIAL ) || self::IsStatus( $p, self::STATUS_REFUND_FULL );
	}

	public static function IsRenewal( \EDD_Payment $p ) :bool {
		return self::IsStatus( $p, self::STATUS_RENEWAL );
	}

	public static function IsStatus( \EDD_Payment $p, string $status ) :bool {
		return $p->status === $status;
	}

	public static function IsValidStatus( string $status ) :bool {
		return in_array( strtolower( $status ), edd_get_payment_status_keys() );
	}
}