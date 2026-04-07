<?php
/**
 * Idempotent per-order spin grants.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Order_Grants
 */
class Nera_STW_Order_Grants {

	/**
	 * Record grant (idempotent). Returns true if a new row was inserted.
	 *
	 * @param int $order_id Order ID.
	 * @param int $product_id Product ID.
	 * @param int $user_id User ID.
	 * @param int $spins Spins granted.
	 * @return bool
	 */
	public static function try_insert( $order_id, $product_id, $user_id, $spins ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_order_grants';
		$now   = current_time( 'mysql' );
		$sql   = $wpdb->prepare(
			"INSERT IGNORE INTO {$table} (order_id, product_id, user_id, spins_granted, created_at) VALUES (%d, %d, %d, %d, %s)",
			absint( $order_id ),
			absint( $product_id ),
			absint( $user_id ),
			max( 0, (int) $spins ),
			$now
		);
		$wpdb->query( $sql );
		return $wpdb->rows_affected > 0;
	}
}
