<?php
/**
 * REST API for Spin To Win.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_REST_Controller
 */
class Nera_STW_REST_Controller {

	const NS = 'nera-stw/v1';

	/**
	 * Register routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 */
	public static function register_routes() {
		register_rest_route(
			self::NS,
			'/product/(?P<id>\d+)/state',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_state' ),
				'permission_callback' => array( __CLASS__, 'require_login' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $v ) {
							return absint( $v ) > 0;
						},
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/product/(?P<id>\d+)/spin',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'post_spin' ),
				'permission_callback' => array( __CLASS__, 'require_login' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $v ) {
							return absint( $v ) > 0;
						},
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/product/(?P<id>\d+)/spin/ack',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'post_spin_ack' ),
				'permission_callback' => array( __CLASS__, 'require_login' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $v ) {
							return absint( $v ) > 0;
						},
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/product/(?P<id>\d+)/spin/turbo',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'post_spin_turbo' ),
				'permission_callback' => array( __CLASS__, 'require_login' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $v ) {
							return absint( $v ) > 0;
						},
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/product/(?P<id>\d+)/prizes',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_prizes' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $v ) {
							return absint( $v ) > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * @return bool|WP_Error
	 */
	public static function require_login() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You must be logged in.', 'nera-spin-to-win' ), array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * GET state.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_state( $request ) {
		$product_id = absint( $request['id'] );
		$user_id    = get_current_user_id();

		if ( ! Nera_STW_Product_Meta::is_enabled( $product_id ) ) {
			return new WP_Error( 'stw_not_enabled', __( 'Spin To Win is not enabled for this product.', 'nera-spin-to-win' ), array( 'status' => 404 ) );
		}

		$items = Nera_STW_Product_Meta::get_public_wheel_items( $product_id );
		$row   = Nera_STW_Balances::get_row( $user_id, $product_id );

		$pending_row = Nera_STW_Spin_Session::get_pending( $user_id, $product_id );
		$active_spin = null;
		if ( $pending_row ) {
			$active_spin = Nera_STW_Spin_Session::row_to_active_spin( $pending_row );
		}

		return rest_ensure_response(
			array(
				'product_id'       => $product_id,
				'wheel_items'      => $items,
				'earned'           => $row ? (int) $row->earned : 0,
				'used'             => $row ? (int) $row->used : 0,
				'remaining_spins'  => Nera_STW_Balances::get_remaining( $user_id, $product_id ),
				'history'          => Nera_STW_Spin_Service::get_history( $user_id, $product_id, 20 ),
				'feature_enabled'  => nera_stw_feature_enabled(),
				'active_spin'      => $active_spin,
			)
		);
	}

	/**
	 * POST spin.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function post_spin( $request ) {
		$product_id = absint( $request['id'] );
		$user_id    = get_current_user_id();

		Nera_STW_Spin_Session::acquire_mysql_lock( $user_id, $product_id );
		try {
			$pending = Nera_STW_Spin_Session::get_pending( $user_id, $product_id );
			if ( $pending ) {
				return rest_ensure_response( Nera_STW_Spin_Session::row_to_spin_response( $pending ) );
			}

			$result = Nera_STW_Spin_Service::spin( $user_id, $product_id );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			Nera_STW_Spin_Session::save_pending( $user_id, $product_id, $result );

			return rest_ensure_response( $result );
		} finally {
			Nera_STW_Spin_Session::release_mysql_lock( $user_id, $product_id );
		}
	}

	/**
	 * POST spin acknowledgment (client finished animation / saw result).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function post_spin_ack( $request ) {
		$product_id = absint( $request['id'] );
		$user_id    = get_current_user_id();

		if ( ! Nera_STW_Product_Meta::is_enabled( $product_id ) ) {
			return new WP_Error( 'stw_not_enabled', __( 'Spin To Win is not enabled for this product.', 'nera-spin-to-win' ), array( 'status' => 404 ) );
		}

		Nera_STW_Spin_Session::acquire_mysql_lock( $user_id, $product_id );
		try {
			$ok = Nera_STW_Spin_Session::acknowledge( $user_id, $product_id );
			return rest_ensure_response(
				array(
					'acknowledged' => $ok,
				)
			);
		} finally {
			Nera_STW_Spin_Session::release_mysql_lock( $user_id, $product_id );
		}
	}

	/**
	 * GET prizes — public prize listing for spin-to-win products (physical segments only).
	 * Returns the same shape as the instant-wins API so the shared Vue component can consume it.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_prizes( $request ) {
		$product_id = absint( $request['id'] );

		// Rate limiting: 30 req/min per IP per product (mirrors instant-wins API).
		$rate_check = self::check_prizes_rate_limit( $product_id );
		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		// Cache check (60s TTL).
		$cache_key   = 'nera_stw_prizes_cache_' . $product_id;
		$cached_data = get_transient( $cache_key );
		if ( false !== $cached_data ) {
			return rest_ensure_response( array( 'success' => true, 'data' => $cached_data, 'cached' => true ) );
		}

		// Validate product exists.
		if ( ! function_exists( 'wc_get_product' ) ) {
			return new WP_Error( 'wc_unavailable', __( 'WooCommerce is not available.', 'nera-spin-to-win' ), array( 'status' => 500 ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->exists() ) {
			return new WP_Error( 'invalid_product', __( 'Product not found.', 'nera-spin-to-win' ), array( 'status' => 404 ) );
		}

		// Build the prize list from physical segments only.
		$data = self::build_prizes_data( $product_id );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		set_transient( $cache_key, $data, 60 );

		return rest_ensure_response( array( 'success' => true, 'data' => $data, 'cached' => false ) );
	}

	/**
	 * Build prizes data — physical and woo_wallet segments + winners from history.
	 *
	 * @param int $product_id Product ID.
	 * @return array|WP_Error
	 */
	private static function build_prizes_data( $product_id ) {
		global $wpdb;

		$segments = Nera_STW_Product_Meta::get_segments( $product_id );

		// Filter to enabled physical or woo_wallet segments.
		$prize_segments = array_filter(
			$segments,
			function ( $seg ) {
				if ( empty( $seg['enabled'] ) ) {
					return false;
				}
				return in_array( $seg['type'], array( 'physical', 'woo_wallet' ), true );
			}
		);

		$history_table = $wpdb->prefix . 'nera_stw_history';
		$prizes        = array();
		$total_stock   = 0;
		$total_won     = 0;

		foreach ( $prize_segments as $seg ) {
			$seg_id     = $seg['id'];
			$seg_type   = $seg['type'];
			$image_url  = ! empty( $seg['image_url'] ) ? $seg['image_url'] : null;

			if ( 'physical' === $seg_type ) {
				$stock_cap = isset( $seg['stock'] ) ? (int) $seg['stock'] : 0;

				// Current remaining from the authoritative stock table; fall back to meta cap.
				$remaining = Nera_STW_Segment_Stock::get_remaining( $product_id, $seg_id );

				// won_count = how many have been awarded (cap minus remaining).
				$won_count = max( 0, $stock_cap - $remaining );

				// Recent winners (up to 10) with name privacy.
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT h.user_id, h.order_id, h.id AS spin_id, u.display_name
						 FROM {$history_table} h
						 LEFT JOIN {$wpdb->users} u ON u.ID = h.user_id
						 WHERE h.product_id = %d AND h.segment_id = %s AND h.prize_type = 'physical'
						 ORDER BY h.id DESC
						 LIMIT 10",
						$product_id,
						$seg_id
					)
				);

				$winners = array();
				foreach ( $rows as $row ) {
					$winners[] = array(
						'details'       => self::format_winner_name( $row->display_name ),
						'ticket_number' => $row->order_id ? 'Order #' . $row->order_id : 'Spin #' . $row->spin_id,
					);
				}

				$prize_title = ! empty( $seg['physical_title'] ) ? $seg['physical_title'] : $seg['label'];

				$prizes[] = array(
					'id'              => $seg_id,
					'title'           => $prize_title,
					'image'           => $image_url,
					'total_available' => $stock_cap,
					'won_count'       => $won_count,
					'winners'         => $winners,
				);

				$total_stock += $stock_cap;
				$total_won   += $won_count;
			} else {
				// woo_wallet — capped when the `stock` key is present (including 0 = sold out); uncapped when absent.
				$has_cap   = isset( $seg['stock'] );
				$stock_cap = $has_cap ? (int) $seg['stock'] : 0;

				// Recent winners (up to 10) with name privacy — same query for both branches.
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT h.user_id, h.order_id, h.id AS spin_id, u.display_name
						 FROM {$history_table} h
						 LEFT JOIN {$wpdb->users} u ON u.ID = h.user_id
						 WHERE h.product_id = %d AND h.segment_id = %s AND h.prize_type = 'woo_wallet'
						 ORDER BY h.id DESC
						 LIMIT 10",
						$product_id,
						$seg_id
					)
				);

				$winners = array();
				foreach ( $rows as $row ) {
					$winners[] = array(
						'details'       => self::format_winner_name( $row->display_name ),
						'ticket_number' => $row->order_id ? 'Order #' . $row->order_id : 'Spin #' . $row->spin_id,
					);
				}

				if ( $has_cap ) {
					// Capped wallet segment (incl. stock = 0) — mirror the physical branch's accounting.
					$remaining = Nera_STW_Segment_Stock::get_remaining( $product_id, $seg_id );
					$won_count = max( 0, $stock_cap - $remaining );

					$prizes[] = array(
						'id'              => $seg_id,
						'title'           => $seg['label'],
						'image'           => $image_url,
						'total_available' => $stock_cap,
						'won_count'       => $won_count,
						'winners'         => $winners,
					);

					$total_stock += $stock_cap;
					$total_won   += $won_count;
				} else {
					// Uncapped wallet — unlimited supply; count awarded from history.
					$won_count = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*)
							 FROM {$history_table}
							 WHERE product_id = %d AND segment_id = %s AND prize_type = 'woo_wallet'",
							$product_id,
							$seg_id
						)
					);

					$prizes[] = array(
						'id'              => $seg_id,
						'title'           => $seg['label'],
						'image'           => $image_url,
						'total_available' => null,
						'won_count'       => $won_count,
						'winners'         => $winners,
					);

					// Wallet entries are unlimited — skip total_available accumulation.
					$total_won += $won_count;
				}
			}
		}

		return array(
			'prizes' => $prizes,
			'stats'  => array(
				'total_available' => $total_stock,
				'total_won'       => $total_won,
			),
		);
	}

	/**
	 * Format winner display name as "First L." for privacy.
	 *
	 * @param string|null $display_name WordPress display_name.
	 * @return string
	 */
	private static function format_winner_name( $display_name ) {
		$name = sanitize_text_field( (string) $display_name );
		if ( '' === $name ) {
			return 'Winner';
		}

		// Already formatted.
		if ( preg_match( '/^[A-Za-z]+\s+[A-Z]\.$/', $name ) ) {
			return $name;
		}

		$parts = explode( ' ', $name );
		if ( count( $parts ) >= 2 ) {
			$last_initial = strtoupper( substr( $parts[ count( $parts ) - 1 ], 0, 1 ) );
			return $parts[0] . ' ' . $last_initial . '.';
		}

		return $name;
	}

	/**
	 * Check rate limit: 30 requests per minute per IP per product.
	 *
	 * @param int $product_id Product ID.
	 * @return true|WP_Error
	 */
	private static function check_prizes_rate_limit( $product_id ) {
		$ip         = self::get_client_ip();
		$rate_key   = 'nera_stw_prizes_rate_' . md5( $ip . '_' . $product_id );
		$req_count  = get_transient( $rate_key );

		if ( false === $req_count ) {
			set_transient( $rate_key, 1, MINUTE_IN_SECONDS );
			return true;
		}

		if ( $req_count >= 30 ) {
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Rate limit exceeded. Maximum 30 requests per minute.', 'nera-spin-to-win' ),
				array( 'status' => 429 )
			);
		}

		set_transient( $rate_key, $req_count + 1, MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Get client IP from server superglobal, respecting common proxy headers.
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);
		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
						return $ip;
					}
				}
			}
		}
		return '0.0.0.0';
	}

	/**
	 * POST spin/turbo — resolve all remaining spins at once and return the batch.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function post_spin_turbo( $request ) {
		$product_id = absint( $request['id'] );
		$user_id    = get_current_user_id();

		Nera_STW_Spin_Session::acquire_mysql_lock( $user_id, $product_id );
		try {
			// Clear any stale pending session before a batch run.
			Nera_STW_Spin_Session::acknowledge( $user_id, $product_id );

			$batch = Nera_STW_Spin_Service::spin_all( $user_id, $product_id );
			if ( is_wp_error( $batch ) ) {
				return $batch;
			}
			return rest_ensure_response( $batch );
		} finally {
			Nera_STW_Spin_Session::release_mysql_lock( $user_id, $product_id );
		}
	}
}

// Bust the prizes cache when segment settings are saved on the product.
add_action(
	'woocommerce_update_product',
	function ( $product_id ) {
		delete_transient( 'nera_stw_prizes_cache_' . $product_id );
	},
	10,
	1
);
