<?php
/**
 * Product meta helpers for Spin To Win.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Product_Meta
 */
class Nera_STW_Product_Meta {

	const META_ENABLED  = '_nera_stw_enabled';
	const META_SEGMENTS = '_nera_stw_segments_json';

	/**
	 * Is spin enabled for product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function is_enabled( $product_id ) {
		return 'yes' === get_post_meta( $product_id, self::META_ENABLED, true );
	}

	/**
	 * Raw segments JSON from post meta.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function get_segments_json( $product_id ) {
		return (string) get_post_meta( $product_id, self::META_SEGMENTS, true );
	}

	/**
	 * Parsed and normalized segments (ordered array).
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_segments( $product_id ) {
		$raw = self::get_segments_json( $product_id );
		if ( '' === $raw ) {
			return array();
		}
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}
		$out = array();
		foreach ( $decoded as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id = isset( $row['id'] ) ? sanitize_text_field( (string) $row['id'] ) : '';
			if ( '' === $id ) {
				continue;
			}
			$type = isset( $row['type'] ) ? sanitize_key( $row['type'] ) : 'no_win';
			if ( ! in_array( $type, array( 'woo_wallet', 'physical', 'no_win' ), true ) ) {
				$type = 'no_win';
			}
			$weight = isset( $row['weight'] ) ? (float) $row['weight'] : 1.0;
			if ( $weight <= 0 ) {
				$weight = 0.0001;
			}
			$seg = array(
				'id'     => $id,
				'label'  => isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : $id,
				'type'   => $type,
				'weight' => $weight,
			);
			$image_id = isset( $row['image_id'] ) ? absint( $row['image_id'] ) : 0;
			if ( $image_id > 0 ) {
				$seg['image_id'] = $image_id;
			}

			$image_url = '';
			if ( $image_id > 0 ) {
				$image_url = wp_get_attachment_image_url( $image_id, 'large' );
			}
			if ( ! $image_url && ! empty( $row['image_url'] ) ) {
				$image_url = esc_url_raw( (string) $row['image_url'] );
			}
			if ( ! empty( $image_url ) ) {
				$seg['image_url'] = $image_url;
			}

			if ( 'woo_wallet' === $type ) {
				$seg['wallet_amount'] = isset( $row['wallet_amount'] ) ? (float) $row['wallet_amount'] : 0;
				if ( $seg['wallet_amount'] < 0 ) {
					$seg['wallet_amount'] = 0;
				}
			}
			if ( 'physical' === $type ) {
				$seg['physical_title'] = isset( $row['physical_title'] ) ? sanitize_text_field( (string) $row['physical_title'] ) : $seg['label'];
				$seg['stock']          = isset( $row['stock'] ) ? max( 0, (int) $row['stock'] ) : 0;
			}
			$out[] = $seg;
		}
		return $out;
	}

	/**
	 * Public wheel items for REST (no secrets).
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_public_wheel_items( $product_id ) {
		$segments = self::get_segments( $product_id );
		$items    = array();
		foreach ( $segments as $i => $seg ) {
			$item = array(
				'index' => $i,
				'label' => $seg['label'],
				'type'  => $seg['type'],
			);
			if ( ! empty( $seg['image_url'] ) ) {
				$item['image_url'] = $seg['image_url'];
			}
			$items[] = $item;
		}
		return $items;
	}

	/**
	 * Find segment by index.
	 *
	 * @param int $product_id Product ID.
	 * @param int $index Index.
	 * @return array<string, mixed>|null
	 */
	public static function get_segment_by_index( $product_id, $index ) {
		$segments = self::get_segments( $product_id );
		if ( ! isset( $segments[ $index ] ) ) {
			return null;
		}
		return $segments[ $index ];
	}

	/**
	 * Find segment by id.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $segment_id Segment id.
	 * @return array<string, mixed>|null
	 */
	public static function get_segment_by_id( $product_id, $segment_id ) {
		foreach ( self::get_segments( $product_id ) as $seg ) {
			if ( $seg['id'] === $segment_id ) {
				return $seg;
			}
		}
		return null;
	}
}
