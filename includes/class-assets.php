<?php
/**
 * Asset enqueueing for the Spin To Win plugin.
 *
 * Replaces the nera_enqueue_spin_to_win() function that previously lived in
 * the theme's functions.php, making the plugin fully self-contained.
 *
 * Dev mode: add define('NERA_STW_DEV', true) to wp-config.php and run
 * `npm run dev` inside the plugin directory (port 5174).
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Assets
 */
class Nera_STW_Assets {

	const SCRIPT_HANDLE  = 'nera-stw-app';
	const STYLE_HANDLE   = 'nera-stw-styles';
	const DATA_OBJECT    = 'neraSpinToWin';
	const DEV_SERVER_URL = 'http://localhost:5174';
	const DEV_ENTRY      = 'src/spin-to-win.js';

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 16 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_page_compat_css' ), 16 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Enqueue assets and localise config on the spin route (logged-in users only).
	 */
	public static function enqueue() {
		$product_id = absint( get_query_var( 'nera_spin_product' ) );
		if ( $product_id < 1 || ! is_user_logged_in() ) {
			return;
		}

		// Poppins — only if not already loaded by the active theme.
		if (
			! wp_style_is( 'nera-google-fonts', 'enqueued' ) &&
			! wp_style_is( 'nera-google-fonts', 'registered' )
		) {
			wp_enqueue_style(
				'nera-stw-fonts',
				'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap',
				array(),
				null
			);
		}

		$competitions_url = home_url( '/' );
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop = wc_get_page_permalink( 'shop' );
			if ( ! empty( $shop ) ) {
				$competitions_url = $shop;
			}
		}

		$data = array(
			'restUrl'         => rest_url( 'nera-stw/v1/' ),
			'productId'       => $product_id,
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'competitionsUrl' => esc_url( $competitions_url ),
			'strings'         => self::strings(),
		);

		if ( self::is_dev_server_running() ) {
			add_action( 'wp_head', array( __CLASS__, 'inject_vite_client' ), 1 );
			add_action( 'wp_footer', array( __CLASS__, 'inject_dev_entry' ), 5 );
			// Register a dummy handle so wp_localize_script has something to attach to.
			wp_register_script( self::SCRIPT_HANDLE, '', array(), false, true );
			wp_enqueue_script( self::SCRIPT_HANDLE );
		} else {
			self::enqueue_from_manifest();
		}

		wp_localize_script( self::SCRIPT_HANDLE, self::DATA_OBJECT, $data );
	}

	/**
	 * Load JS + CSS from the Vite manifest.
	 */
	private static function enqueue_from_manifest() {
		$manifest_path = NERA_STW_PLUGIN_DIR . 'dist/.vite/manifest.json';
		if ( ! file_exists( $manifest_path ) ) {
			return;
		}

		$manifest = json_decode( file_get_contents( $manifest_path ), true ); // phpcs:ignore
		if ( ! isset( $manifest[ self::DEV_ENTRY ] ) ) {
			return;
		}

		$entry  = $manifest[ self::DEV_ENTRY ];
		$js_url = NERA_STW_PLUGIN_URL . 'dist/' . $entry['file'];

		wp_enqueue_script( self::SCRIPT_HANDLE, $js_url, array(), NERA_STW_VERSION, true );
		add_filter( 'script_loader_tag', array( __CLASS__, 'add_module_type' ), 10, 2 );

		if ( ! empty( $entry['css'] ) ) {
			foreach ( $entry['css'] as $i => $css_file ) {
				$css_url = NERA_STW_PLUGIN_URL . 'dist/' . $css_file;
				wp_enqueue_style(
					self::STYLE_HANDLE . ( $i > 0 ? "-{$i}" : '' ),
					$css_url,
					array(),
					NERA_STW_VERSION
				);
			}
		}
	}

	/**
	 * Add type="module" to the plugin's script tag.
	 *
	 * @param string $tag    Script HTML tag.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public static function add_module_type( $tag, $handle ) {
		if ( self::SCRIPT_HANDLE !== $handle ) {
			return $tag;
		}
		return str_replace( '<script ', '<script type="module" ', $tag );
	}

	/**
	 * Inject Vite HMR client (dev mode only).
	 */
	public static function inject_vite_client() {
		echo '<script type="module" src="' . esc_url( self::DEV_SERVER_URL . '/@vite/client' ) . '"></script>' . "\n";
	}

	/**
	 * Inject the plugin's Vite dev entry module (dev mode only).
	 */
	public static function inject_dev_entry() {
		echo '<script type="module" src="' . esc_url( self::DEV_SERVER_URL . '/' . self::DEV_ENTRY ) . '"></script>' . "\n";
	}

	/**
	 * Check whether the plugin's Vite dev server is reachable.
	 *
	 * @return bool
	 */
	private static function is_dev_server_running() {
		if ( ! defined( 'NERA_STW_DEV' ) || ! NERA_STW_DEV ) {
			return false;
		}
		$ctx      = stream_context_create( array( 'http' => array( 'timeout' => 1 ) ) );
		$response = @file_get_contents( self::DEV_SERVER_URL . '/@vite/client', false, $ctx ); // phpcs:ignore
		return $response !== false;
	}

	/**
	 * Localised UI strings.
	 *
	 * @return array
	 */
	private static function strings() {
		$defaults = array(
			'spinNow'              => __( 'Spin now', 'nera-spin-to-win' ),
			'turbo'                => __( 'Turbo mode', 'nera-spin-to-win' ),
			'loading'              => __( 'Loading\u2026', 'nera-spin-to-win' ),
			'noSpins'              => __( 'No spins left.', 'nera-spin-to-win' ),
			'noSpinsBody'          => __( 'Purchase more tickets to earn spins.', 'nera-spin-to-win' ),
			'close'                => __( 'Close', 'nera-spin-to-win' ),
			'historyTitle'         => __( 'History', 'nera-spin-to-win' ),
			'prizesTitle'          => __( 'All prizes', 'nera-spin-to-win' ),
			'spinsLeft'            => __( 'spins left', 'nera-spin-to-win' ),
			'emptyHistory'         => __( 'No spin history yet. Start spinning to discover your next prize!', 'nera-spin-to-win' ),
			'tryAgain'             => __( 'Close, but not this time.', 'nera-spin-to-win' ),
			'tryAgainBody'         => __( "The fun's not over... Give it another spin!", 'nera-spin-to-win' ),
			'youWon'               => __( 'You won!', 'nera-spin-to-win' ),
			'wonWallet'            => __( 'Site credit added: {amount}', 'nera-spin-to-win' ),
			'wonPhysical'          => __( 'Your prize will be fulfilled \u2014 our team may email you if needed.', 'nera-spin-to-win' ),
			'spinAgain'            => __( 'Spin now', 'nera-spin-to-win' ),
			'error'                => __( 'Something went wrong', 'nera-spin-to-win' ),
			'errorBody'            => '',
			'disabled'             => __( 'Spin To Win is temporarily unavailable.', 'nera-spin-to-win' ),
			'tooltipTurbo'         => __( 'Spin immediately using a shorter wheel animation.', 'nera-spin-to-win' ),
			'tooltipSpin'          => __( 'Spin the wheel with the full-length animation.', 'nera-spin-to-win' ),
			'tooltipModalSpin'     => __( 'Spin again with the full-length animation.', 'nera-spin-to-win' ),
			'tooltipClose'         => __( 'Close this message', 'nera-spin-to-win' ),
			'competitions'         => __( 'Competitions', 'nera-spin-to-win' ),
			'tooltipCompetitions'  => __( 'Browse competitions to buy more tickets', 'nera-spin-to-win' ),
			'viewAllPrizes'        => __( 'View all prizes', 'nera-spin-to-win' ),
			'tooltipViewAllPrizes' => __( 'See the full prize list', 'nera-spin-to-win' ),
		);

		if ( class_exists( 'Nera_STW_ACF_Copy_Settings' ) ) {
			return Nera_STW_ACF_Copy_Settings::merge_localized_strings( $defaults );
		}

		return $defaults;
	}

	/**
	 * Enqueue page layout compatibility CSS on the spin route (all users).
	 */
	public static function enqueue_page_compat_css() {
		if ( absint( get_query_var( 'nera_spin_product' ) ) < 1 ) {
			return;
		}
		wp_enqueue_style(
			'nera-stw-page-compat',
			NERA_STW_PLUGIN_URL . 'assets/spin-to-win-page.css',
			array(),
			NERA_STW_VERSION
		);
	}

	/**
	 * Add body class on the spin route.
	 *
	 * @param array $classes Classes.
	 * @return array
	 */
	public static function body_class( $classes ) {
		if ( absint( get_query_var( 'nera_spin_product' ) ) > 0 ) {
			$classes[] = 'nera-spin-to-win-page';
		}
		return $classes;
	}
}
