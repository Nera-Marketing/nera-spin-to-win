<?php
/**
 * Plugin Name: Nera Spin To Win
 * Description: Spin wheel for lottery competitions — spins from ticket purchases, site credit and physical prizes.
 * Version: 1.1.0
 * Author: Nera
 * Text Domain: nera-spin-to-win
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

define( 'NERA_STW_VERSION', '1.1.0' );

// Auto-update checker — pulls releases from GitHub.
require_once plugin_dir_path( __FILE__ ) . 'lib/plugin-update-checker/load-v5p5.php';
$nera_stw_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/Nera-Marketing/nera-spin-to-win/',
	__FILE__,
	'nera-spin-to-win'
);
$nera_stw_update_checker->getVcsSource()->enableReleaseAssets();
define( 'NERA_STW_PLUGIN_FILE', __FILE__ );
define( 'NERA_STW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NERA_STW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once NERA_STW_PLUGIN_DIR . 'includes/class-database.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-product-meta.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-balances.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-order-grants.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-segment-stock.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-spin-service.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-spin-session.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-rest-controller.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-hooks.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-admin-product.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-admin-physical-wins.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-frontend.php';
require_once NERA_STW_PLUGIN_DIR . 'includes/class-assets.php';

// Register REST routes as early as possible after plugin file load.
if ( class_exists( 'Nera_STW_REST_Controller' ) ) {
	Nera_STW_REST_Controller::init();
}

/**
 * Public URL for the spin screen for a competition product.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function nera_stw_get_spin_url( $product_id ) {
	$product_id = absint( $product_id );
	if ( $product_id < 1 ) {
		return '';
	}
	return home_url( user_trailingslashit( 'spin-to-win/product/' . $product_id ) );
}

/**
 * Bootstrap plugin.
 */
function nera_stw_init() {
	Nera_STW_Frontend::init();
	Nera_STW_Assets::init();

	load_plugin_textdomain( 'nera-spin-to-win', false, dirname( plugin_basename( NERA_STW_PLUGIN_FILE ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Nera_STW_Database::maybe_upgrade();
	Nera_STW_Hooks::init();
	Nera_STW_Admin_Settings::init();
	Nera_STW_Admin_Product::init();
	Nera_STW_Admin_Physical_Wins::init();
}
add_action( 'plugins_loaded', 'nera_stw_init', 20 );

/**
 * Activation: create tables and flush rewrites.
 */
function nera_stw_activate() {
	require_once NERA_STW_PLUGIN_DIR . 'includes/class-database.php';
	require_once NERA_STW_PLUGIN_DIR . 'includes/class-frontend.php';
	Nera_STW_Database::install();
	Nera_STW_Frontend::register_rewrite();
	flush_rewrite_rules( false );
}
register_activation_hook( __FILE__, 'nera_stw_activate' );

/**
 * WooCommerce HPOS compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
