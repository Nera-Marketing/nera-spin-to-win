<?php
/**
 * Settings: feature flag.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Admin_Settings
 */
class Nera_STW_Admin_Settings {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
	}

	/**
	 * Submenu under WooCommerce.
	 */
	public static function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Spin To Win', 'nera-spin-to-win' ),
			__( 'Spin To Win', 'nera-spin-to-win' ),
			'manage_woocommerce',
			'nera-spin-to-win',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register option.
	 */
	public static function register_setting() {
		register_setting(
			'nera_stw_settings',
			'nera_stw_enabled',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $v ) {
					return 'yes' === $v ? 'yes' : 'no';
				},
				'default'           => 'yes',
			)
		);
	}

	/**
	 * Settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( isset( $_POST['nera_stw_settings_submit'] ) && check_admin_referer( 'nera_stw_settings' ) ) {
			update_option( 'nera_stw_enabled', isset( $_POST['nera_stw_enabled'] ) ? 'yes' : 'no' );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'nera-spin-to-win' ) . '</p></div>';
		}
		$enabled = get_option( 'nera_stw_enabled', 'yes' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Spin To Win', 'nera-spin-to-win' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'nera_stw_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Spin To Win', 'nera-spin-to-win' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nera_stw_enabled" value="yes" <?php checked( $enabled, 'yes' ); ?> />
								<?php esc_html_e( 'When disabled, REST endpoints reject spins and new order grants stop.', 'nera-spin-to-win' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save', 'nera-spin-to-win' ), 'primary', 'nera_stw_settings_submit' ); ?>
			</form>
		</div>
		<?php
	}
}
