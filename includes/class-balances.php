<?php
/**
 * User/product spin balances.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Balances
 */
class Nera_STW_Balances {

	/**
	 * Get balance row.
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @return object|null { earned, used, ... }
	 */
	public static function get_row( $user_id, $product_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_balances';
		$uid   = absint( $user_id );
		$pid   = absint( $product_id );
		$wpdb->suppress_errors( true );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND product_id = %d",
				$uid,
				$pid
			)
		);
		$wpdb->suppress_errors( false );
		return $row ? $row : null;
	}

	/**
	 * Remaining spins.
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @return int
	 */
	public static function get_remaining( $user_id, $product_id ) {
		$row = self::get_row( $user_id, $product_id );
		if ( ! $row ) {
			return 0;
		}
		return max( 0, (int) $row->earned - (int) $row->used );
	}

	/**
	 * Add earned spins (idempotent grant flow should call once per order/product).
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @param int $spins Spins to add.
	 * @return bool
	 */
	public static function add_earned( $user_id, $product_id, $spins ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_balances';
		$uid   = absint( $user_id );
		$pid   = absint( $product_id );
		$add   = max( 0, (int) $spins );
		if ( $add < 1 || $uid < 1 || $pid < 1 ) {
			return false;
		}
		$now = current_time( 'mysql' );
		$row = self::get_row( $uid, $pid );
		if ( $row ) {
			$wpdb->update(
				$table,
				array(
					'earned'     => (int) $row->earned + $add,
					'updated_at' => $now,
				),
				array(
					'user_id'    => $uid,
					'product_id' => $pid,
				),
				array( '%d', '%s' ),
				array( '%d', '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'user_id'    => $uid,
					'product_id' => $pid,
					'earned'     => $add,
					'used'       => 0,
					'updated_at' => $now,
				),
				array( '%d', '%d', '%d', '%d', '%s' )
			);
		}
		return true;
	}

	/**
	 * Increment used by 1 if remaining > 0.
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @return bool True if consumed.
	 */
	public static function try_consume_one( $user_id, $product_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_balances';
		$uid   = absint( $user_id );
		$pid   = absint( $product_id );
		$now   = current_time( 'mysql' );
		$sql   = $wpdb->prepare(
			"UPDATE {$table} SET used = used + 1, updated_at = %s
			WHERE user_id = %d AND product_id = %d AND earned > used",
			$now,
			$uid,
			$pid
		);
		$wpdb->query( $sql );
		return $wpdb->rows_affected > 0;
	}

	/**
	 * Roll back one used spin (e.g. after failed fulfillment).
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 */
	public static function rollback_used_one( $user_id, $product_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_balances';
		$uid   = absint( $user_id );
		$pid   = absint( $product_id );
		$now   = current_time( 'mysql' );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET used = GREATEST(used - 1, 0), updated_at = %s WHERE user_id = %d AND product_id = %d",
				$now,
				$uid,
				$pid
			)
		);
	}
}
