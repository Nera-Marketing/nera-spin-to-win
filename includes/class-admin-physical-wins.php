<?php
/**
 * Admin list: recent physical Spin To Win prizes.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Admin_Physical_Wins
 */
class Nera_STW_Admin_Physical_Wins {

	/**
	 * Init.
	 */
	public static function init() {
		add_submenu_page(
			'woocommerce',
			__( 'Spin To Win — Physical prizes', 'nera-spin-to-win' ),
			__( 'STW Physical wins', 'nera-spin-to-win' ),
			'manage_woocommerce',
			'nera-stw-physical-wins',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Render table.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'nera_stw_history';
		$rows  = $wpdb->get_results(
			"SELECT h.*, u.display_name, u.user_email
			FROM {$table} h
			LEFT JOIN {$wpdb->users} u ON u.ID = h.user_id
			WHERE h.prize_type = 'physical'
			ORDER BY h.id DESC
			LIMIT 100",
			ARRAY_A
		);

		echo '<div class="wrap"><h1>' . esc_html__( 'Spin To Win — Physical prizes', 'nera-spin-to-win' ) . '</h1>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Date', 'nera-spin-to-win' ) . '</th>';
		echo '<th>' . esc_html__( 'User', 'nera-spin-to-win' ) . '</th>';
		echo '<th>' . esc_html__( 'Product ID', 'nera-spin-to-win' ) . '</th>';
		echo '<th>' . esc_html__( 'Prize', 'nera-spin-to-win' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $rows ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No physical wins yet.', 'nera-spin-to-win' ) . '</td></tr>';
		} else {
			foreach ( $rows as $r ) {
				$product = wc_get_product( (int) $r['product_id'] );
				$pname   = $product ? $product->get_name() : '#' . (int) $r['product_id'];
				echo '<tr>';
				echo '<td>' . esc_html( $r['created_at'] ) . '</td>';
				echo '<td>' . esc_html( (string) $r['display_name'] ) . '<br><small>' . esc_html( (string) $r['user_email'] ) . '</small></td>';
				echo '<td>' . esc_html( (string) $r['product_id'] ) . ' — ' . esc_html( $pname ) . '</td>';
				echo '<td>' . esc_html( $r['prize_label'] ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody></table></div>';
	}
}
