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
}
