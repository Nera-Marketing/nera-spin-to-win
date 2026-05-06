<?php
/**
 * Plugin Update Checker bootstrap for GitHub releases.
 *
 * Loaded from the main plugin file when active, and from `mu-plugins/nera-spin-to-win-updates.php`
 * when the plugin is installed but deactivated — WordPress does not load inactive plugins, so PUC
 * would otherwise never run.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

use YahnisElsts\PluginUpdateChecker\v5p5\Vcs\Api as PucVcsApi;
use YahnisElsts\PluginUpdateChecker\v5p5\Vcs\GitHubApi;

/**
 * Register GitHub updates (PUC). Safe to call once per request; idempotent.
 *
 * @param string $plugin_file Absolute path to `nera-spin-to-win.php`.
 * @return void
 */
function nera_stw_bootstrap_plugin_update_checker( $plugin_file ) {
	if ( defined( 'NERA_STW_UPDATE_CHECKER_BOOTSTRAPPED' ) ) {
		return;
	}

	if ( ! is_string( $plugin_file ) || ! is_readable( $plugin_file ) ) {
		return;
	}

	if ( defined( 'NERA_STW_DISABLE_GITHUB_UPDATES' ) && NERA_STW_DISABLE_GITHUB_UPDATES ) {
		define( 'NERA_STW_UPDATE_CHECKER_BOOTSTRAPPED', true );
		return;
	}

	$plugin_dir = plugin_dir_path( $plugin_file );
	$nera_stw_github_repo_default = 'https://github.com/Nera-Marketing/nera-spin-to-win/';
	if ( defined( 'NERA_STW_GITHUB_REPO_URL' ) && is_string( NERA_STW_GITHUB_REPO_URL ) && NERA_STW_GITHUB_REPO_URL !== '' ) {
		$nera_stw_github_repo_default = NERA_STW_GITHUB_REPO_URL;
	}
	$nera_stw_github_repo = apply_filters( 'nera_stw_github_repo_url', $nera_stw_github_repo_default );

	$nera_stw_puc_loader = $plugin_dir . 'lib/plugin-update-checker/load-v5p5.php';
	if ( is_readable( $nera_stw_puc_loader ) ) {
		require_once $nera_stw_puc_loader;
		$nera_stw_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			$nera_stw_github_repo,
			$plugin_file,
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
					'latest_tag'     => $strategies['latest_tag'],
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

		define( 'NERA_STW_UPDATE_CHECKER_BOOTSTRAPPED', true );
	}
}
