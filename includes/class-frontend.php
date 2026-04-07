<?php
/**
 * Public rewrite and template routing for Spin To Win screen.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Frontend
 */
class Nera_STW_Frontend {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrite' ), 1 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_filter( 'pre_handle_404', array( __CLASS__, 'pre_handle_404' ), 10, 2 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
	}

	/**
	 * Prevent WordPress core from handling this virtual route as 404.
	 *
	 * @param mixed    $preempt  Existing preempt value.
	 * @param WP_Query $wp_query Main query.
	 * @return mixed
	 */
	public static function pre_handle_404( $preempt, $wp_query ) {
		if ( absint( get_query_var( 'nera_spin_product' ) ) < 1 ) {
			return $preempt;
		}

		if ( is_object( $wp_query ) ) {
			$wp_query->is_404 = false;
			if ( isset( $wp_query->query_vars['error'] ) ) {
				$wp_query->query_vars['error'] = '';
			}
		}

		status_header( 200 );
		return true;
	}

	/**
	 * Register rewrite rules.
	 */
	public static function register_rewrite() {
		add_rewrite_rule(
			'^spin-to-win/product/([0-9]+)/?$',
			'index.php?nera_spin_product=$matches[1]',
			'top'
		);
	}

	/**
	 * Query vars.
	 *
	 * @param array $vars Vars.
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'nera_spin_product';
		return $vars;
	}

	/**
	 * Load template when query var is set.
	 * Priority: 1) active theme's page-templates/spin-to-win.php, 2) plugin fallback.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public static function template_include( $template ) {
		$pid = absint( get_query_var( 'nera_spin_product' ) );
		if ( $pid < 1 ) {
			return $template;
		}

		// 1. Child/active-theme override — highest priority, preserves backward compat.
		$child = get_stylesheet_directory() . '/page-templates/spin-to-win.php';
		if ( file_exists( $child ) ) {
			self::fix_query_state();
			return $child;
		}

		// 2. Plugin-bundled fallback — works on any theme.
		$plugin_tpl = NERA_STW_PLUGIN_DIR . 'templates/spin-to-win.php';
		if ( file_exists( $plugin_tpl ) ) {
			self::fix_query_state();
			return $plugin_tpl;
		}

		return $template;
	}

	/**
	 * Ensure WordPress does not treat the virtual route as a 404.
	 */
	private static function fix_query_state() {
		global $wp_query;
		if ( is_object( $wp_query ) ) {
			$wp_query->is_404 = false;
			if ( isset( $wp_query->query_vars['error'] ) ) {
				$wp_query->query_vars['error'] = '';
			}
		}
		status_header( 200 );
	}
}
