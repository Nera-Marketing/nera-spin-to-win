<?php
/**
 * Lottery / WooCommerce hooks.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Hooks
 */
class Nera_STW_Hooks {

	/**
	 * Init hooks.
	 */
	public static function init() {
		// Grant spins after payment / paid-like statuses (not on unpaid checkout placement).
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'grant_spins_for_order' ), 20, 1 );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'grant_spins_for_order' ), 20, 1 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'grant_spins_for_order' ), 20, 1 );
		// Priority below 10 so CTA renders before woocommerce_order_details_table (priority 10).
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'thankyou_spin_cta' ), 5, 1 );
		add_action( 'woocommerce_order_details_before_order_table', array( __CLASS__, 'view_order_spin_cta' ), 5, 1 );
	}

	/**
	 * Grant spins from ticket quantity per lottery line item (idempotent per order + product).
	 *
	 * @param int $order_id Order ID.
	 */
	public static function grant_spins_for_order( $order_id ) {
		if ( ! nera_stw_feature_enabled() ) {
			return;
		}

		if ( ! function_exists( 'lty_is_lottery_product' ) ) {
			return;
		}

		if ( ! class_exists( 'Nera_STW_Order_Grants' ) ) {
			return;
		}

		$order_id = absint( $order_id );
		$order    = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$blocked = array( 'cancelled', 'failed', 'refunded' );
		if ( $order->has_status( $blocked ) ) {
			return;
		}

		$user_id = (int) $order->get_user_id();
		if ( $user_id < 1 ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item ) || ! method_exists( $item, 'get_product' ) ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product || ! lty_is_lottery_product( $product ) ) {
				continue;
			}

			$product_id = $product->get_id();
			if ( ! Nera_STW_Product_Meta::is_enabled( $product_id ) ) {
				continue;
			}

			$qty = (int) $item->get_quantity();
			if ( $qty < 1 ) {
				continue;
			}

			if ( ! Nera_STW_Order_Grants::try_insert( $order_id, $product_id, $user_id, $qty ) ) {
				continue;
			}

			Nera_STW_Balances::add_earned( $user_id, $product_id, $qty );
		}
	}

	/**
	 * Spin To Win links for order line items (lottery + STW enabled).
	 *
	 * @param WC_Order $order Order.
	 * @return array<int, array{url: string, label: string}> Keyed by product ID.
	 */
	private static function collect_stw_links_for_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return array();
		}

		if ( ! function_exists( 'lty_is_lottery_product' ) || ! function_exists( 'nera_stw_get_spin_url' ) ) {
			return array();
		}

		$links = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product || ! lty_is_lottery_product( $product ) ) {
				continue;
			}
			$pid = $product->get_id();
			if ( ! Nera_STW_Product_Meta::is_enabled( $pid ) ) {
				continue;
			}
			$url = nera_stw_get_spin_url( $pid );
			if ( '' === $url ) {
				continue;
			}
			$links[ $pid ] = array(
				'url'   => $url,
				'label' => $product->get_name(),
			);
		}

		return $links;
	}

	/**
	 * Thank-you CTA: link to spin page for eligible competitions.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function thankyou_spin_cta( $order_id ) {
		if ( ! nera_stw_feature_enabled() ) {
			return;
		}

		$order_id = absint( $order_id );
		$order    = wc_get_order( $order_id );
		if ( ! $order || ! $order->has_status( apply_filters( 'nera_stw_thankyou_allowed_statuses', array( 'processing', 'completed' ) ) ) ) {
			return;
		}

		if ( ! is_user_logged_in() || (int) $order->get_user_id() !== get_current_user_id() ) {
			return;
		}

		$links = self::collect_stw_links_for_order( $order );
		if ( empty( $links ) ) {
			return;
		}

		echo '<div class="w-full mb-8 p-6 rounded-2xl border border-primary/20 bg-secondary/80 shadow-sm">';
		echo '<div class="flex flex-col sm:flex-row flex-wrap gap-3">';
		foreach ( $links as $row ) {
			echo '<a class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary to-indigo-600 text-white font-semibold rounded-xl hover:opacity-90 transition-all shadow-sm" href="' . esc_url( $row['url'] ) . '">';
			echo '<span class="material-symbols-outlined text-xl">casino</span>';
			echo esc_html( sprintf( /* translators: %s: product name */ __( 'Spin the wheel — %s', 'nera-spin-to-win' ), $row['label'] ) );
			echo '</a>';
		}
		echo '</div></div>';
	}

	/**
	 * My Account order view: primary buttons to spin page (same products as thank-you CTA).
	 *
	 * Guarded so checkout thank-you (which also fires woocommerce_order_details_before_order_table) does not duplicate output.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function view_order_spin_cta( $order ) {
		if ( ! nera_stw_feature_enabled() ) {
			return;
		}

		if ( ! is_user_logged_in() || ! is_account_page() || ! is_wc_endpoint_url( 'view-order' ) ) {
			return;
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( (int) $order->get_user_id() !== get_current_user_id() ) {
			return;
		}

		$allowed = apply_filters( 'nera_stw_view_order_allowed_statuses', array( 'processing', 'completed', 'on-hold' ) );
		if ( ! $order->has_status( $allowed ) ) {
			return;
		}

		$links = self::collect_stw_links_for_order( $order );
		if ( empty( $links ) ) {
			return;
		}

		echo '<div class="flex flex-col sm:flex-row flex-wrap gap-3">';
		foreach ( $links as $row ) {
			echo '<a class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary to-indigo-600 text-white font-semibold rounded-xl hover:opacity-90 transition-all shadow-sm" href="' . esc_url( $row['url'] ) . '">';
			echo '<span class="material-symbols-outlined text-xl">casino</span>';
			echo esc_html( sprintf( /* translators: %s: product name */ __( 'Spin the wheel — %s', 'nera-spin-to-win' ), $row['label'] ) );
			echo '</a>';
		}
		echo '</div>';
	}
}
