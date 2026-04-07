<?php
/**
 * Pending spin session (resume after navigation) — one row per user + product.
 *
 * Concurrency: `Nera_STW_REST_Controller::post_spin()` wraps spin + pending save in a MySQL
 * `GET_LOCK` per user/product so two simultaneous POSTs cannot both pass the pending check
 * and consume twice. Re-posting while a row is still `pending` returns the same result
 * without consuming another spin.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Spin_Session
 */
class Nera_STW_Spin_Session {

	const STATUS_PENDING  = 'pending';
	const STATUS_REVEALED = 'revealed';

	/**
	 * MySQL advisory lock name (max 64 chars on MySQL).
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @return string
	 */
	private static function lock_name( $user_id, $product_id ) {
		return 'nera_stw_' . absint( $user_id ) . '_' . absint( $product_id );
	}

	/**
	 * Acquire MySQL GET_LOCK; no-op if unavailable.
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public static function acquire_mysql_lock( $user_id, $product_id ) {
		global $wpdb;
		$name = self::lock_name( $user_id, $product_id );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- GET_LOCK placeholder.
		$wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 10)', $name ) );
	}

	/**
	 * Release MySQL advisory lock.
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public static function release_mysql_lock( $user_id, $product_id ) {
		global $wpdb;
		$name = self::lock_name( $user_id, $product_id );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
	}

	/**
	 * Get pending row as associative array or null.
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_pending( $user_id, $product_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_pending_spins';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND product_id = %d AND status = %s",
				absint( $user_id ),
				absint( $product_id ),
				self::STATUS_PENDING
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Save pending spin after server-side spin completes (before client reveals).
	 *
	 * @param int                  $user_id User ID.
	 * @param int                  $product_id Product ID.
	 * @param array<string, mixed> $spin_result Result from Nera_STW_Spin_Service::spin (array, not WP_Error).
	 * @return bool
	 */
	public static function save_pending( $user_id, $product_id, $spin_result ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_pending_spins';
		$uid   = absint( $user_id );
		$pid   = absint( $product_id );

		$details = isset( $spin_result['details'] ) ? $spin_result['details'] : array();
		$now     = current_time( 'mysql' );

		$data = array(
			'user_id'          => $uid,
			'product_id'       => $pid,
			'winning_index'    => isset( $spin_result['winning_index'] ) ? (int) $spin_result['winning_index'] : 0,
			'prize_type'       => isset( $spin_result['prize_type'] ) ? sanitize_text_field( (string) $spin_result['prize_type'] ) : '',
			'prize_label'      => isset( $spin_result['prize_label'] ) ? sanitize_text_field( (string) $spin_result['prize_label'] ) : '',
			'details_json'     => wp_json_encode( $details ),
			'remaining_spins'  => isset( $spin_result['remaining_spins'] ) ? absint( $spin_result['remaining_spins'] ) : 0,
			'status'           => self::STATUS_PENDING,
			'created_at'       => $now,
			'updated_at'       => $now,
		);

		// Replace so re-spin after reveal always gets a fresh row.
		$wpdb->replace(
			$table,
			$data,
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		return true;
	}

	/**
	 * Map DB row to spin API response shape.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	public static function row_to_spin_response( $row ) {
		$details = array();
		if ( ! empty( $row['details_json'] ) ) {
			$decoded = json_decode( (string) $row['details_json'], true );
			$details = is_array( $decoded ) ? $decoded : array();
		}

		return array(
			'winning_index'   => isset( $row['winning_index'] ) ? (int) $row['winning_index'] : 0,
			'prize_type'      => isset( $row['prize_type'] ) ? (string) $row['prize_type'] : '',
			'prize_label'     => isset( $row['prize_label'] ) ? (string) $row['prize_label'] : '',
			'remaining_spins' => isset( $row['remaining_spins'] ) ? (int) $row['remaining_spins'] : 0,
			'details'         => $details,
		);
	}

	/**
	 * Payload for GET state (active_spin).
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	public static function row_to_active_spin( $row ) {
		$base = self::row_to_spin_response( $row );
		return array_merge(
			$base,
			array(
				'created_at' => isset( $row['created_at'] ) ? (string) $row['created_at'] : '',
			)
		);
	}

	/**
	 * Mark pending spin as revealed (after client animation + modal). Deletes row.
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @return bool Whether a row was removed.
	 */
	public static function acknowledge( $user_id, $product_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_pending_spins';
		$sql   = $wpdb->prepare(
			"DELETE FROM {$table} WHERE user_id = %d AND product_id = %d AND status = %s",
			absint( $user_id ),
			absint( $product_id ),
			self::STATUS_PENDING
		);
		$wpdb->query( $sql );
		return $wpdb->rows_affected > 0;
	}
}
