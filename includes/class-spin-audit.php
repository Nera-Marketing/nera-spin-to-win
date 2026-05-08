<?php
/**
 * Spin audit log — records every spin's seed, eligibility snapshot, and outcome
 * so any single result can be deterministically reproduced from the row alone.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Spin_Audit
 */
class Nera_STW_Spin_Audit {

	const TABLE = 'nera_stw_spin_audit';

	/**
	 * Fully-qualified table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Generate a UUIDv4 string. Uses random_bytes (CSPRNG).
	 *
	 * @return string
	 */
	public static function uuid4() {
		$b    = random_bytes( 16 );
		$b[6] = chr( ( ord( $b[6] ) & 0x0f ) | 0x40 );
		$b[8] = chr( ( ord( $b[8] ) & 0x3f ) | 0x80 );
		$h    = bin2hex( $b );
		return substr( $h, 0, 8 ) . '-' . substr( $h, 8, 4 ) . '-' . substr( $h, 12, 4 ) . '-' . substr( $h, 16, 4 ) . '-' . substr( $h, 20, 12 );
	}

	/**
	 * Insert one audit row.
	 *
	 * Expected keys: spin_uid, user_id, product_id, server_seed, client_seed,
	 * nonce, eligible (array), total_weight (float), cut (float),
	 * outcome_index (int), outcome_segment (string).
	 *
	 * @param array<string, mixed> $row Audit fields.
	 * @return bool True on insert success.
	 */
	public static function record( array $row ) {
		global $wpdb;
		$result = $wpdb->insert(
			self::table(),
			array(
				'spin_uid'        => isset( $row['spin_uid'] ) ? (string) $row['spin_uid'] : self::uuid4(),
				'user_id'         => absint( $row['user_id'] ?? 0 ),
				'product_id'      => absint( $row['product_id'] ?? 0 ),
				'server_seed'     => isset( $row['server_seed'] ) ? (string) $row['server_seed'] : '',
				'client_seed'     => isset( $row['client_seed'] ) ? (string) $row['client_seed'] : '',
				'nonce'           => isset( $row['nonce'] ) ? (int) $row['nonce'] : 0,
				'eligible_json'   => wp_json_encode( $row['eligible'] ?? array() ),
				'total_weight'    => (float) ( $row['total_weight'] ?? 0 ),
				'cut'             => (float) ( $row['cut'] ?? 0 ),
				'outcome_index'   => (int) ( $row['outcome_index'] ?? 0 ),
				'outcome_segment' => isset( $row['outcome_segment'] ) ? (string) $row['outcome_segment'] : '',
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%f', '%f', '%d', '%s', '%s' )
		);
		return false !== $result;
	}

	/**
	 * Look up the next nonce for a (user, product) pair and atomically increment it.
	 * Stored in user_meta to avoid an extra table for v1.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 * @return int Next nonce (always >= 1).
	 */
	public static function next_nonce( $user_id, $product_id ) {
		$key  = '_nera_stw_nonce_' . absint( $product_id );
		$prev = (int) get_user_meta( absint( $user_id ), $key, true );
		$next = $prev + 1;
		update_user_meta( absint( $user_id ), $key, $next );
		return $next;
	}
}
