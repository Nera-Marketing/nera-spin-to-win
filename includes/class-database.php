<?php
/**
 * Database schema and migrations.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Database
 */
class Nera_STW_Database {

	const DB_VERSION = '1.1.0';
	const OPTION_KEY = 'nera_stw_db_version';

	/**
	 * Install tables.
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$balances = $wpdb->prefix . 'nera_stw_balances';
		$grants   = $wpdb->prefix . 'nera_stw_order_grants';
		$history  = $wpdb->prefix . 'nera_stw_history';
		$stock    = $wpdb->prefix . 'nera_stw_segment_stock';
		$pending  = $wpdb->prefix . 'nera_stw_pending_spins';

		$sql = "
CREATE TABLE {$balances} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  product_id bigint(20) unsigned NOT NULL,
  earned int(10) unsigned NOT NULL DEFAULT 0,
  used int(10) unsigned NOT NULL DEFAULT 0,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user_product (user_id, product_id),
  KEY product_id (product_id)
) {$charset_collate};

CREATE TABLE {$grants} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  order_id bigint(20) unsigned NOT NULL,
  product_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  spins_granted int(10) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY order_product (order_id, product_id),
  KEY user_id (user_id)
) {$charset_collate};

CREATE TABLE {$history} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  product_id bigint(20) unsigned NOT NULL,
  order_id bigint(20) unsigned DEFAULT NULL,
  segment_id varchar(64) NOT NULL,
  prize_type varchar(32) NOT NULL,
  prize_label varchar(255) NOT NULL,
  prize_value longtext NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY user_product (user_id, product_id),
  KEY prize_type (prize_type)
) {$charset_collate};

CREATE TABLE {$stock} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  product_id bigint(20) unsigned NOT NULL,
  segment_id varchar(64) NOT NULL,
  remaining int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY prod_seg (product_id, segment_id),
  KEY product_id (product_id)
) {$charset_collate};

CREATE TABLE {$pending} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  product_id bigint(20) unsigned NOT NULL,
  winning_index int(11) NOT NULL DEFAULT 0,
  prize_type varchar(32) NOT NULL DEFAULT '',
  prize_label varchar(255) NOT NULL DEFAULT '',
  details_json longtext NULL,
  remaining_spins int(10) unsigned NOT NULL DEFAULT 0,
  status varchar(16) NOT NULL DEFAULT 'pending',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user_product (user_id, product_id),
  KEY product_id (product_id),
  KEY status (status)
) {$charset_collate};
";

		dbDelta( $sql );

		update_option( self::OPTION_KEY, self::DB_VERSION, true );
	}

	/**
	 * Run install if version mismatch.
	 */
	public static function maybe_upgrade() {
		$v = get_option( self::OPTION_KEY, '' );
		if ( $v !== self::DB_VERSION ) {
			self::install();
		}
	}
}
