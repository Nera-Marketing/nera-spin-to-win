<?php
/**
 * Plugin Name: Nera – Spin To Win
 * Description: Spin wheel for lottery competitions — spins from ticket purchases, site credit and physical prizes.
 * Version: 1.1.11
 * Author: Nera
 * Text Domain: nera-spin-to-win
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 *
 * @package Nera_Spin_To_Win
 */

use YahnisElsts\PluginUpdateChecker\v5p5\Vcs\Api as PucVcsApi;
use YahnisElsts\PluginUpdateChecker\v5p5\Vcs\GitHubApi;

defined( 'ABSPATH' ) || exit;

define( 'NERA_STW_VERSION', '1.1.11' );
define( 'NERA_STW_PLUGIN_FILE', __FILE__ );
define( 'NERA_STW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NERA_STW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * GitHub updates (Plugin Update Checker v5.5). Parity with `nera-instant-win-threshold`.
 *
 * Disable: `define( 'NERA_STW_DISABLE_GITHUB_UPDATES', true );`
 * Private repo: `define( 'NERA_STW_GITHUB_TOKEN', 'ghp_...' );`
 * Repo URL: `define( 'NERA_STW_GITHUB_REPO_URL', 'https://github.com/Owner/repo/' );` or filter `nera_stw_github_repo_url`.
 *
 * GitHub’s “latest” release is the newest *created* release, not always the highest semver. We therefore run the
 * `latest_tag` strategy (semver-sorted) before `latest_release`, then force the download URL to the published
 * `nera-spin-to-win-{version}.zip` release asset (same layout as `release.sh`), not the tag source archive.
 *
 * `setReleaseFilter` + `maxReleases` > 1 avoids relying only on `GET .../releases/latest` (404 when GitHub has no
 * “latest”, or odd ordering). `setBranch( 'main' )` matches the default branch (PUC’s default is `master`).
 *
 * Plugin list / Dashboard → Updates: `assets/icon-128x128.png`, `assets/icon-256x256.png`.
 */
if ( ! defined( 'NERA_STW_DISABLE_GITHUB_UPDATES' ) || ! NERA_STW_DISABLE_GITHUB_UPDATES ) {
	$nera_stw_github_repo_default = 'https://github.com/Nera-Marketing/nera-spin-to-win/';
	if ( defined( 'NERA_STW_GITHUB_REPO_URL' ) && is_string( NERA_STW_GITHUB_REPO_URL ) && NERA_STW_GITHUB_REPO_URL !== '' ) {
		$nera_stw_github_repo_default = NERA_STW_GITHUB_REPO_URL;
	}
	$nera_stw_github_repo = apply_filters( 'nera_stw_github_repo_url', $nera_stw_github_repo_default );

	$nera_stw_puc_loader = NERA_STW_PLUGIN_DIR . 'lib/plugin-update-checker/load-v5p5.php';
	if ( is_readable( $nera_stw_puc_loader ) ) {
		require_once $nera_stw_puc_loader;
		$nera_stw_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			$nera_stw_github_repo,
			__FILE__,
			'nera-spin-to-win',
			6
		);
		$nera_stw_update_checker->setBranch( 'main' );

		if ( defined( 'NERA_STW_GITHUB_TOKEN' ) && is_string( NERA_STW_GITHUB_TOKEN ) && NERA_STW_GITHUB_TOKEN !== '' ) {
			$nera_stw_update_checker->setAuthentication( NERA_STW_GITHUB_TOKEN );
		}

		$nera_stw_puc_vcs = $nera_stw_update_checker->getVcsApi();
		if ( $nera_stw_puc_vcs instanceof GitHubApi ) {
			$nera_stw_puc_vcs->setReleaseFilter(
				static function ( $version_number, $release_object ) {
					unset( $version_number, $release_object );
					return true;
				},
				PucVcsApi::RELEASE_FILTER_SKIP_PRERELEASE,
				20
			);
			$nera_stw_puc_vcs->enableReleaseAssets();
		}

		add_filter(
			$nera_stw_update_checker->getUniqueName( 'vcs_update_detection_strategies' ),
			static function ( $strategies ) {
				if ( ! isset( $strategies['latest_tag'], $strategies['latest_release'] ) ) {
					return $strategies;
				}
				$ordered = array(
					'latest_tag'    => $strategies['latest_tag'],
					'latest_release' => $strategies['latest_release'],
				);
				foreach ( $strategies as $key => $callback ) {
					if ( isset( $ordered[ $key ] ) ) {
						continue;
					}
					$ordered[ $key ] = $callback;
				}
				return $ordered;
			},
			10,
			1
		);

		add_filter(
			$nera_stw_update_checker->getUniqueName( 'request_info_result' ),
			static function ( $info ) use ( $nera_stw_github_repo ) {
				if ( ! is_object( $info ) || empty( $info->version ) ) {
					return $info;
				}
				$ver = preg_replace( '/^v/i', '', (string) $info->version );
				$tag = 'v' . $ver;
				$path = (string) wp_parse_url( $nera_stw_github_repo, PHP_URL_PATH );
				$parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
				if ( count( $parts ) >= 2 ) {
					$owner = $parts[0];
					$repo  = $parts[1];
					$info->download_url = sprintf(
						'https://github.com/%s/%s/releases/download/%s/nera-spin-to-win-%s.zip',
						$owner,
						$repo,
						$tag,
						$ver
					);
				}
				return $info;
			},
			10,
			1
		);
	}
}

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
require_once NERA_STW_PLUGIN_DIR . 'includes/class-acf-copy-settings.php';

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

	Nera_STW_ACF_Copy_Settings::init();

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
