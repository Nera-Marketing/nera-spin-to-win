<?php
/**
 * Physical prize segment stock.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Segment_Stock
 */
class Nera_STW_Segment_Stock {

	/**
	 * Ensure rows exist for physical segments (on product save).
	 *
	 * @param int   $product_id Product ID.
	 * @param array $segments Segments from Nera_STW_Product_Meta::get_segments().
	 */
	public static function sync_initial_from_segments( $product_id, $segments ) {
		foreach ( $segments as $seg ) {
			if ( 'physical' !== $seg['type'] ) {
				continue;
			}
			$sid = $seg['id'];
			$cap = isset( $seg['stock'] ) ? (int) $seg['stock'] : 0;
			self::ensure_row( $product_id, $sid, $cap );
		}
	}

	/**
	 * Insert row with remaining = cap if missing.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $segment_id Segment id.
	 * @param int    $initial_cap Initial stock cap.
	 */
	public static function ensure_row( $product_id, $segment_id, $initial_cap ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_segment_stock';
		$pid   = absint( $product_id );
		$sid   = sanitize_text_field( $segment_id );
		$cap   = max( 0, (int) $initial_cap );

		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND segment_id = %s",
				$pid,
				$sid
			)
		);
		if ( $existing > 0 ) {
			return;
		}
		$wpdb->insert(
			$table,
			array(
				'product_id' => $pid,
				'segment_id' => $sid,
				'remaining'  => $cap,
			),
			array( '%d', '%s', '%d' )
		);
	}

	/**
	 * Remaining stock for physical segment.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $segment_id Segment id.
	 * @return int
	 */
	public static function get_remaining( $product_id, $segment_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_segment_stock';
		$val   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT remaining FROM {$table} WHERE product_id = %d AND segment_id = %s",
				absint( $product_id ),
				sanitize_text_field( $segment_id )
			)
		);
		return null === $val ? 0 : (int) $val;
	}

	/**
	 * Atomically decrement if remaining > 0.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $segment_id Segment id.
	 * @return bool
	 */
	public static function try_decrement( $product_id, $segment_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_segment_stock';
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET remaining = remaining - 1 WHERE product_id = %d AND segment_id = %s AND remaining > 0",
				absint( $product_id ),
				sanitize_text_field( $segment_id )
			)
		);
		return $wpdb->rows_affected > 0;
	}

	/**
	 * Increment remaining (rollback).
	 *
	 * @param int    $product_id Product ID.
	 * @param string $segment_id Segment id.
	 */
	public static function increment( $product_id, $segment_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_segment_stock';
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET remaining = remaining + 1 WHERE product_id = %d AND segment_id = %s",
				absint( $product_id ),
				sanitize_text_field( $segment_id )
			)
		);
	}
}
