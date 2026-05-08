<?php
/**
 * ACF options: editable Spin To Win page hero and dialog copy.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_ACF_Copy_Settings
 */
class Nera_STW_ACF_Copy_Settings {

	/**
	 * Init.
	 */
	public static function init() {
		// Field registration lives in the theme's WooCommerce ACF options group.
	}

	/**
	 * Back-compat no-op: fields now live on Theme Settings > WooCommerce.
	 */
	public static function register_acf() {
		return;
	}

	/**
	 * Read optional text from ACF options; empty string falls back to default.
	 *
	 * @param string $field_name ACF field name.
	 * @param string $default    Default when ACF missing or value empty.
	 * @return string
	 */
	public static function get_option_text( $field_name, $default ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}
		$value = get_field( $field_name, 'option' );
		if ( is_string( $value ) ) {
			$trimmed = trim( $value );
			if ( '' !== $trimmed ) {
				return $trimmed;
			}
		}
		return $default;
	}

	/**
	 * Hero badge (pill above title).
	 *
	 * @return string
	 */
	public static function get_hero_badge() {
		return self::get_option_text(
			'stw_hero_badge',
			__( 'Spin to win', 'nera-spin-to-win' )
		);
	}

	/**
	 * Optional hero heading override; empty means use product name.
	 *
	 * @return string
	 */
	public static function get_hero_heading_override() {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}
		$value = get_field( 'stw_hero_heading', 'option' );
		if ( is_string( $value ) ) {
			return trim( $value );
		}
		return '';
	}

	/**
	 * Hero intro paragraph under the title.
	 *
	 * @return string
	 */
	public static function get_hero_intro() {
		return self::get_option_text(
			'stw_hero_intro',
			__( 'Use your spins from ticket purchases — every spin is a shot at site credit or prizes.', 'nera-spin-to-win' )
		);
	}

	/**
	 * Merge ACF overrides into the localized React strings array.
	 *
	 * @param array $defaults Keyed defaults (already translated).
	 * @return array
	 */
	public static function merge_localized_strings( array $defaults ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $defaults;
		}

		$map = array(
			'spinNow'              => 'stw_btn_spin',
			'turbo'                => 'stw_btn_turbo',
			'noSpins'              => 'stw_no_spins_title',
			'noSpinsBody'          => 'stw_no_spins_body',
			'close'                => 'stw_btn_close',
			'tryAgain'             => 'stw_no_win_title',
			'tryAgainBody'         => 'stw_no_win_body',
			'youWon'               => 'stw_win_title',
			'wonWallet'            => 'stw_win_wallet_body',
			'wonPhysical'          => 'stw_win_physical_body',
			'spinAgain'            => 'stw_btn_spin_modal',
			'error'                => 'stw_error_title',
			'errorBody'            => 'stw_error_body',
			'tooltipTurbo'         => 'stw_tooltip_turbo',
			'tooltipSpin'          => 'stw_tooltip_spin',
			'tooltipModalSpin'     => 'stw_tooltip_modal_spin',
			'tooltipClose'         => 'stw_tooltip_close',
			'competitions'         => 'stw_competitions',
			'tooltipCompetitions'  => 'stw_tooltip_competitions',
			'viewAllPrizes'        => 'stw_view_all_prizes',
			'tooltipViewAllPrizes' => 'stw_tooltip_view_all_prizes',
		);

		$out = $defaults;
		foreach ( $map as $string_key => $acf_name ) {
			$default = isset( $defaults[ $string_key ] ) ? $defaults[ $string_key ] : '';
			$out[ $string_key ] = self::get_option_text( $acf_name, $default );
		}

		return $out;
	}

	/**
	 * Fields appended by the theme WooCommerce ACF settings group.
	 *
	 * @return array
	 */
	public static function get_woocommerce_tab_fields() {
		return self::get_woocommerce_accordion_fields();
	}

	/**
	 * Fields appended by the theme WooCommerce ACF settings group.
	 *
	 * @return array
	 */
	public static function get_woocommerce_accordion_fields() {
		return array_merge(
			array( self::accordion_field( 'field_stw_accordion_spin_to_win', __( 'Spin To Win', 'nera-spin-to-win' ) ) ),
			self::hero_fields(),
			self::dialog_result_fields(),
			self::dialog_action_fields(),
			array( self::accordion_endpoint( 'field_stw_accordion_spin_to_win_end' ) )
		);
	}

	/**
	 * @param string $key   Field key.
	 * @param string $label Accordion label.
	 * @return array
	 */
	private static function accordion_field( $key, $label ) {
		return array(
			'key'               => $key,
			'label'             => $label,
			'name'              => '',
			'type'              => 'accordion',
			'placement'         => 'top',
			'open'              => 0,
			'multi_expand'      => 0,
			'endpoint'          => 0,
		);
	}

	/**
	 * @param string $key Field key.
	 * @return array
	 */
	private static function accordion_endpoint( $key ) {
		return array(
			'key'      => $key,
			'label'    => '',
			'name'     => '',
			'type'     => 'accordion',
			'endpoint' => 1,
		);
	}

	/**
	 * @return array[]
	 */
	private static function hero_fields() {
		return array(
			self::text_field(
				'field_stw_hero_badge',
				'stw_hero_badge',
				__( 'Badge text', 'nera-spin-to-win' ),
				__( 'Small pill above the competition title.', 'nera-spin-to-win' ),
				__( 'Spin to win', 'nera-spin-to-win' )
			),
			self::text_field(
				'field_stw_hero_heading',
				'stw_hero_heading',
				__( 'Heading override (optional)', 'nera-spin-to-win' ),
				__( 'Leave empty to show the competition product name.', 'nera-spin-to-win' ),
				''
			),
			self::textarea_field(
				'field_stw_hero_intro',
				'stw_hero_intro',
				__( 'Intro text', 'nera-spin-to-win' ),
				__( 'Paragraph under the heading.', 'nera-spin-to-win' ),
				__( 'Use your spins from ticket purchases — every spin is a shot at site credit or prizes.', 'nera-spin-to-win' )
			),
		);
	}

	/**
	 * @return array[]
	 */
	private static function dialog_result_fields() {
		return array(
			self::text_field(
				'field_stw_no_spins_title',
				'stw_no_spins_title',
				__( 'No spins left — title', 'nera-spin-to-win' ),
				'',
				__( 'No spins left.', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_no_spins_body',
				'stw_no_spins_body',
				__( 'No spins left — body', 'nera-spin-to-win' ),
				'',
				__( 'Purchase more tickets to earn spins.', 'nera-spin-to-win' )
			),
			self::text_field(
				'field_stw_no_win_title',
				'stw_no_win_title',
				__( 'No win — title', 'nera-spin-to-win' ),
				'',
				__( 'Close, but not this time.', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_no_win_body',
				'stw_no_win_body',
				__( 'No win — body', 'nera-spin-to-win' ),
				'',
				__( "The fun's not over... Give it another spin!", 'nera-spin-to-win' )
			),
			self::text_field(
				'field_stw_win_title',
				'stw_win_title',
				__( 'Win — title', 'nera-spin-to-win' ),
				__( 'Shown for site credit and physical prizes.', 'nera-spin-to-win' ),
				__( 'You won!', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_win_wallet_body',
				'stw_win_wallet_body',
				__( 'Site credit win — body', 'nera-spin-to-win' ),
				__( 'Use the placeholder {amount} where the credit amount should appear.', 'nera-spin-to-win' ),
				__( 'Site credit added: {amount}', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_win_physical_body',
				'stw_win_physical_body',
				__( 'Physical prize win — body', 'nera-spin-to-win' ),
				'',
				__( 'Your prize will be fulfilled — our team may email you if needed.', 'nera-spin-to-win' )
			),
			self::text_field(
				'field_stw_error_title',
				'stw_error_title',
				__( 'Generic error — title', 'nera-spin-to-win' ),
				__( 'Shown when a spin request fails.', 'nera-spin-to-win' ),
				__( 'Something went wrong', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_error_body',
				'stw_error_body',
				__( 'Generic error — body (optional)', 'nera-spin-to-win' ),
				__( 'If set, replaces the technical error message in the dialog. Leave empty to show the server message.', 'nera-spin-to-win' ),
				''
			),
		);
	}

	/**
	 * @return array[]
	 */
	private static function dialog_action_fields() {
		return array(
			self::text_field(
				'field_stw_btn_turbo',
				'stw_btn_turbo',
				__( 'Turbo mode button', 'nera-spin-to-win' ),
				'',
				__( 'Turbo mode', 'nera-spin-to-win' )
			),
			self::text_field(
				'field_stw_btn_spin',
				'stw_btn_spin',
				__( 'Spin now (main controls)', 'nera-spin-to-win' ),
				'',
				__( 'Spin now', 'nera-spin-to-win' )
			),
			self::text_field(
				'field_stw_btn_spin_modal',
				'stw_btn_spin_modal',
				__( 'Spin now (post-spin dialog)', 'nera-spin-to-win' ),
				'',
				__( 'Spin now', 'nera-spin-to-win' )
			),
			self::text_field(
				'field_stw_competitions',
				'stw_competitions',
				__( 'Competitions button (no spins dialog)', 'nera-spin-to-win' ),
				'',
				__( 'Competitions', 'nera-spin-to-win' )
			),
			self::text_field(
				'field_stw_btn_close',
				'stw_btn_close',
				__( 'Close (accessibility)', 'nera-spin-to-win' ),
				__( 'Used for close button aria-label fallback.', 'nera-spin-to-win' ),
				__( 'Close', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_tooltip_turbo',
				'stw_tooltip_turbo',
				__( 'Tooltip: Turbo', 'nera-spin-to-win' ),
				'',
				__( 'Spin immediately using a shorter wheel animation.', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_tooltip_spin',
				'stw_tooltip_spin',
				__( 'Tooltip: Spin (main)', 'nera-spin-to-win' ),
				'',
				__( 'Spin the wheel with the full-length animation.', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_tooltip_modal_spin',
				'stw_tooltip_modal_spin',
				__( 'Tooltip: Spin (dialog)', 'nera-spin-to-win' ),
				'',
				__( 'Spin again with the full-length animation.', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_tooltip_close',
				'stw_tooltip_close',
				__( 'Tooltip: Close dialog', 'nera-spin-to-win' ),
				'',
				__( 'Close this message', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_tooltip_competitions',
				'stw_tooltip_competitions',
				__( 'Tooltip: Competitions', 'nera-spin-to-win' ),
				'',
				__( 'Browse competitions to buy more tickets', 'nera-spin-to-win' )
			),
			self::text_field(
				'field_stw_view_all_prizes',
				'stw_view_all_prizes',
				__( 'View all prizes link', 'nera-spin-to-win' ),
				'',
				__( 'View all prizes', 'nera-spin-to-win' )
			),
			self::textarea_field(
				'field_stw_tooltip_view_all_prizes',
				'stw_tooltip_view_all_prizes',
				__( 'Tooltip: View all prizes', 'nera-spin-to-win' ),
				'',
				__( 'See the full prize list', 'nera-spin-to-win' )
			),
		);
	}

	/**
	 * @param string $key          ACF field key.
	 * @param string $name         Field name.
	 * @param string $label        Label.
	 * @param string $instructions Instructions.
	 * @param string $default      Default value.
	 * @return array
	 */
	private static function text_field( $key, $name, $label, $instructions, $default ) {
		return array(
			'key'               => $key,
			'label'             => $label,
			'name'              => $name,
			'type'              => 'text',
			'instructions'      => $instructions,
			'required'          => 0,
			'default_value'     => $default,
			'placeholder'       => '',
			'prepend'           => '',
			'append'            => '',
			'maxlength'         => '',
		);
	}

	/**
	 * @param string $key          ACF field key.
	 * @param string $name         Field name.
	 * @param string $label        Label.
	 * @param string $instructions Instructions.
	 * @param string $default      Default value.
	 * @return array
	 */
	private static function textarea_field( $key, $name, $label, $instructions, $default ) {
		return array(
			'key'               => $key,
			'label'             => $label,
			'name'              => $name,
			'type'              => 'textarea',
			'instructions'      => $instructions,
			'required'          => 0,
			'rows'              => 3,
			'new_lines'         => '',
			'default_value'     => $default,
		);
	}
}
