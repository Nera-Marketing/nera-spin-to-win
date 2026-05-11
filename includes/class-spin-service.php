<?php
/**
 * Weighted spin logic and prize fulfillment.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Spin_Service
 */
class Nera_STW_Spin_Service {

	/**
	 * Perform one spin.
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @return array|WP_Error Result or error.
	 */
	public static function spin( $user_id, $product_id ) {
		if ( ! nera_stw_feature_enabled() ) {
			return new WP_Error( 'stw_disabled', __( 'Spin To Win is disabled.', 'nera-spin-to-win' ), array( 'status' => 403 ) );
		}

		if ( ! Nera_STW_Product_Meta::is_enabled( $product_id ) ) {
			return new WP_Error( 'stw_not_enabled', __( 'Spin To Win is not enabled for this competition.', 'nera-spin-to-win' ), array( 'status' => 400 ) );
		}

		$segments = Nera_STW_Product_Meta::get_segments( $product_id );
		if ( empty( $segments ) ) {
			return new WP_Error( 'stw_no_segments', __( 'No wheel segments configured.', 'nera-spin-to-win' ), array( 'status' => 400 ) );
		}

		if ( Nera_STW_Balances::get_remaining( $user_id, $product_id ) < 1 ) {
			return new WP_Error( 'stw_no_spins', __( 'No spins remaining.', 'nera-spin-to-win' ), array( 'status' => 400 ) );
		}

		if ( ! Nera_STW_Balances::try_consume_one( $user_id, $product_id ) ) {
			return new WP_Error( 'stw_consume_failed', __( 'Could not use a spin.', 'nera-spin-to-win' ), array( 'status' => 409 ) );
		}

		$eligible = self::build_eligible_indices( $product_id, $segments );
		if ( empty( $eligible ) ) {
			Nera_STW_Balances::rollback_used_one( $user_id, $product_id );
			return new WP_Error( 'stw_no_eligible', __( 'No eligible prizes available.', 'nera-spin-to-win' ), array( 'status' => 500 ) );
		}

		$server_seed = bin2hex( random_bytes( 32 ) );
		$client_seed = bin2hex( random_bytes( 32 ) );
		$nonce       = Nera_STW_Spin_Audit::next_nonce( $user_id, $product_id );
		$spin_uid    = Nera_STW_Spin_Audit::uuid4();

		$pick      = self::weighted_pick_index_seeded( $segments, $eligible, $server_seed, $client_seed, $nonce );
		$win_index = $pick['index'];
		$seg       = $segments[ $win_index ];

		// Claim stock for capped segments (physical, or wallet with a stock cap).
		if ( self::segment_consumes_stock( $seg ) ) {
			if ( ! Nera_STW_Segment_Stock::try_decrement( $product_id, $seg['id'] ) ) {
				$fallback = self::find_fallback_no_win_index( $segments, $eligible );
				if ( null !== $fallback ) {
					$win_index = $fallback;
					$seg       = $segments[ $win_index ];
				}
			}
		}

		$fulfill = self::fulfill( $user_id, $product_id, $seg );
		if ( is_wp_error( $fulfill ) ) {
			Nera_STW_Balances::rollback_used_one( $user_id, $product_id );
			if ( self::segment_consumes_stock( $seg ) ) {
				Nera_STW_Segment_Stock::increment( $product_id, $seg['id'] );
			}
			return $fulfill;
		}

		self::log_history( $user_id, $product_id, null, $seg, $fulfill );

		Nera_STW_Spin_Audit::record(
			array(
				'spin_uid'        => $spin_uid,
				'user_id'         => $user_id,
				'product_id'      => $product_id,
				'server_seed'     => $server_seed,
				'client_seed'     => $client_seed,
				'nonce'           => $nonce,
				'eligible'        => self::build_audit_eligible_snapshot( $segments, $eligible ),
				'total_weight'    => $pick['total'],
				'cut'             => $pick['cut'],
				'outcome_index'   => $win_index,
				'outcome_segment' => $seg['id'],
			)
		);

		return array(
			'spin_uid'        => $spin_uid,
			'winning_index'   => $win_index,
			'prize_type'      => $seg['type'],
			'prize_label'     => $seg['label'],
			'remaining_spins' => Nera_STW_Balances::get_remaining( $user_id, $product_id ),
			'details'         => $fulfill,
		);
	}

	/**
	 * Resolve every remaining spin in one call (turbo mode). Each spin is fully
	 * resolved by spin() — RNG, audit, fulfilment, history are all preserved per spin.
	 *
	 * Stops early if a spin returns WP_Error or another process consumes a spin
	 * mid-batch. Capped at 50 to bound request time.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 * @return array{results: array, remaining_spins: int}|WP_Error
	 */
	public static function spin_all( $user_id, $product_id ) {
		if ( ! nera_stw_feature_enabled() ) {
			return new WP_Error( 'stw_disabled', __( 'Spin To Win is disabled.', 'nera-spin-to-win' ), array( 'status' => 403 ) );
		}
		if ( ! Nera_STW_Product_Meta::is_enabled( $product_id ) ) {
			return new WP_Error( 'stw_not_enabled', __( 'Spin To Win is not enabled for this competition.', 'nera-spin-to-win' ), array( 'status' => 400 ) );
		}

		$remaining = Nera_STW_Balances::get_remaining( $user_id, $product_id );
		if ( $remaining < 1 ) {
			return new WP_Error( 'stw_no_spins', __( 'No spins remaining.', 'nera-spin-to-win' ), array( 'status' => 400 ) );
		}

		$cap     = min( $remaining, 50 );
		$results = array();

		for ( $i = 0; $i < $cap; $i++ ) {
			if ( Nera_STW_Balances::get_remaining( $user_id, $product_id ) < 1 ) {
				break;
			}
			$result = self::spin( $user_id, $product_id );
			if ( is_wp_error( $result ) ) {
				break;
			}
			$results[] = $result;
		}

		return array(
			'results'         => $results,
			'remaining_spins' => Nera_STW_Balances::get_remaining( $user_id, $product_id ),
		);
	}

	/**
	 * Compact eligibility snapshot for the audit row.
	 *
	 * @param array $segments All segments.
	 * @param int[] $eligible Eligible indices.
	 * @return array<int, array{i:int,id:string,w:float}>
	 */
	private static function build_audit_eligible_snapshot( $segments, $eligible ) {
		$out = array();
		foreach ( $eligible as $i ) {
			$out[] = array(
				'i'  => (int) $i,
				'id' => isset( $segments[ $i ]['id'] ) ? (string) $segments[ $i ]['id'] : '',
				'w'  => (float) ( $segments[ $i ]['weight'] ?? 0 ),
			);
		}
		return $out;
	}

	/**
	 * Indices that participate in weighted draw.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $segments Segments.
	 * @return int[]
	 */
	private static function build_eligible_indices( $product_id, $segments ) {
		$eligible = array();
		foreach ( $segments as $i => $seg ) {
			if ( empty( $seg['enabled'] ) ) {
				continue;
			}
			// Physical segments are always cap-gated. Wallet segments are gated only
			// when an explicit positive `stock` cap is configured; uncapped wallet
			// segments remain unlimited.
			if ( self::segment_consumes_stock( $seg ) ) {
				$r = Nera_STW_Segment_Stock::get_remaining( $product_id, $seg['id'] );
				if ( $r < 1 ) {
					continue;
				}
			}
			$eligible[] = (int) $i;
		}
		return $eligible;
	}

	/**
	 * Whether a segment participates in the segment-stock cap & decrement flow.
	 *
	 * Physical segments are always capped. Wallet segments are capped only when
	 * `stock` is configured as a positive integer; otherwise they are unlimited
	 * and never consume stock.
	 *
	 * @param array<string, mixed> $seg Segment.
	 * @return bool
	 */
	private static function segment_consumes_stock( $seg ) {
		if ( ! is_array( $seg ) || empty( $seg['type'] ) ) {
			return false;
		}
		if ( 'physical' === $seg['type'] ) {
			return true;
		}
		if ( 'woo_wallet' === $seg['type'] ) {
			// Capped wallet segments (incl. stock = 0 / sold out) consume stock; uncapped wallet does not.
			return isset( $seg['stock'] );
		}
		return false;
	}

	/**
	 * Seeded weighted pick. Deterministic given the same (server_seed, client_seed, nonce):
	 * any audit row can be replayed and verified.
	 *
	 *   r       = HMAC-SHA256(server_seed, client_seed:nonce) → first 13 hex → /2^53
	 *   cut     = r * total_weight
	 *   outcome = first eligible segment whose accumulated weight >= cut
	 *
	 * @param array  $segments    All segments.
	 * @param int[]  $eligible    Eligible indices.
	 * @param string $server_seed 64-char hex.
	 * @param string $client_seed 64-char hex.
	 * @param int    $nonce       Per-(user, product) counter.
	 * @return array{index:int, cut:float, total:float}
	 */
	public static function weighted_pick_index_seeded( $segments, $eligible, $server_seed, $client_seed, $nonce ) {
		$total = 0.0;
		foreach ( $eligible as $i ) {
			$total += (float) $segments[ $i ]['weight'];
		}
		if ( $total <= 0 ) {
			return array(
				'index' => $eligible[0],
				'cut'   => 0.0,
				'total' => 0.0,
			);
		}

		// 14 hex chars = 56 bits; mask to 53 → unbiased uniform in [0, 1).
		$hex = hash_hmac( 'sha256', $client_seed . ':' . $nonce, $server_seed );
		$u53 = hexdec( substr( $hex, 0, 14 ) ) & ( ( 1 << 53 ) - 1 );
		$r   = $u53 / (float) ( 1 << 53 );
		$cut = $r * $total;

		$acc = 0.0;
		foreach ( $eligible as $i ) {
			$acc += (float) $segments[ $i ]['weight'];
			if ( $cut <= $acc ) {
				return array(
					'index' => (int) $i,
					'cut'   => $cut,
					'total' => $total,
				);
			}
		}
		return array(
			'index' => (int) $eligible[ count( $eligible ) - 1 ],
			'cut'   => $cut,
			'total' => $total,
		);
	}

	/**
	 * @param array $segments Segments.
	 * @param array $eligible Eligible indices from current draw.
	 * @return int|null
	 */
	private static function find_fallback_no_win_index( $segments, $eligible ) {
		foreach ( $eligible as $i ) {
			if ( 'no_win' === $segments[ $i ]['type'] ) {
				return (int) $i;
			}
		}
		foreach ( $segments as $i => $seg ) {
			if ( 'no_win' === $seg['type'] ) {
				return (int) $i;
			}
		}
		return null;
	}

	/**
	 * Fulfill prize.
	 *
	 * @param int                  $user_id User ID.
	 * @param int                  $product_id Product ID.
	 * @param array<string, mixed> $seg Segment.
	 * @return array|WP_Error
	 */
	private static function fulfill( $user_id, $product_id, $seg ) {
		switch ( $seg['type'] ) {
			case 'no_win':
				return array(
					'kind' => 'no_win',
				);
			case 'woo_wallet':
				$amount = isset( $seg['wallet_amount'] ) ? (float) $seg['wallet_amount'] : 0;
				if ( $amount <= 0 ) {
					return new WP_Error( 'stw_wallet_zero', __( 'Wallet prize has no credit amount configured.', 'nera-spin-to-win' ), array( 'status' => 500 ) );
				}
				if ( ! function_exists( 'woo_wallet' ) || ! is_object( woo_wallet() ) ) {
					return new WP_Error( 'stw_no_wallet', __( 'Wallet is not available.', 'nera-spin-to-win' ), array( 'status' => 500 ) );
				}
				$product = wc_get_product( $product_id );
				$name    = $product ? $product->get_name() : '';
				/* translators: %s: product name */
				$note = sprintf( __( 'Spin To Win — %s', 'nera-spin-to-win' ), $name );
				$tx   = woo_wallet()->wallet->credit( $user_id, $amount, $note );
				return array(
					'kind'            => 'wallet',
					'amount'          => $amount,
					'transaction_ref' => $tx,
				);
			case 'physical':
				Nera_STW_Spin_Service::notify_admin_physical_win( $user_id, $product_id, $seg );
				return array(
					'kind'  => 'physical',
					'title' => isset( $seg['physical_title'] ) ? $seg['physical_title'] : $seg['label'],
				);
			default:
				return new WP_Error( 'stw_bad_type', __( 'Invalid segment type.', 'nera-spin-to-win' ), array( 'status' => 500 ) );
		}
	}

	/**
	 * Admin email for physical instant prize.
	 *
	 * @param int                  $user_id User ID.
	 * @param int                  $product_id Product ID.
	 * @param array<string, mixed> $seg Segment.
	 */
	public static function notify_admin_physical_win( $user_id, $product_id, $seg ) {
		$admin_email = apply_filters( 'nera_stw_physical_win_admin_email', get_option( 'admin_email' ), $user_id, $product_id, $seg );
		if ( ! is_email( $admin_email ) ) {
			return;
		}
		$user  = get_userdata( $user_id );
		$prod  = wc_get_product( $product_id );
		$title = isset( $seg['physical_title'] ) ? $seg['physical_title'] : $seg['label'];
		/* translators: %s: site name */
		$subject = sprintf( __( '[%s] Physical Spin To Win prize', 'nera-spin-to-win' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
		$lines   = array(
			__( 'A customer won a physical instant prize from Spin To Win.', 'nera-spin-to-win' ),
			'',
			__( 'Prize:', 'nera-spin-to-win' ) . ' ' . $title,
			__( 'Competition product:', 'nera-spin-to-win' ) . ' ' . ( $prod ? $prod->get_name() : '#' . $product_id ),
			__( 'User ID:', 'nera-spin-to-win' ) . ' ' . $user_id,
			__( 'Email:', 'nera-spin-to-win' ) . ' ' . ( $user ? $user->user_email : '—' ),
			__( 'Display name:', 'nera-spin-to-win' ) . ' ' . ( $user ? $user->display_name : '—' ),
		);
		$body = implode( "\n", $lines );
		wp_mail( $admin_email, $subject, $body );
	}

	/**
	 * @param int|null             $order_id Order ID optional.
	 * @param array<string, mixed> $seg Segment.
	 * @param array                $fulfill Fulfillment payload.
	 */
	private static function log_history( $user_id, $product_id, $order_id, $seg, $fulfill ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_history';
		$wpdb->insert(
			$table,
			array(
				'user_id'     => absint( $user_id ),
				'product_id'  => absint( $product_id ),
				'order_id'    => $order_id ? absint( $order_id ) : 0,
				'segment_id'  => $seg['id'],
				'prize_type'  => $seg['type'],
				'prize_label' => $seg['label'],
				'prize_value' => wp_json_encode( $fulfill ),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Recent history for user/product.
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @param int $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_history( $user_id, $product_id, $limit = 20 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_history';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT prize_label, prize_type, created_at FROM {$table} WHERE user_id = %d AND product_id = %d ORDER BY id DESC LIMIT %d",
				absint( $user_id ),
				absint( $product_id ),
				absint( $limit )
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}
}

/**
 * Global feature flag.
 *
 * @return bool
 */
function nera_stw_feature_enabled() {
	return 'yes' === get_option( 'nera_stw_enabled', 'yes' );
}
