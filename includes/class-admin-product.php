<?php
/**
 * Product data tab: Spin To Win segments.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_STW_Admin_Product
 */
class Nera_STW_Admin_Product {

	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ), 20, 1 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );
	}

	/**
	 * Tab.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public static function add_tab( $tabs ) {
		$tabs['nera_spin_to_win'] = array(
			'label'    => __( 'Spin To Win', 'nera-spin-to-win' ),
			'target'   => 'nera_spin_to_win_data',
			'class'    => array( 'show_if_lottery' ),
			'priority' => 85,
		);
		return $tabs;
	}

	/**
	 * Scripts for product edit.
	 *
	 * @param string $hook Hook.
	 */
	public static function admin_scripts( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}
		wp_enqueue_media();
		wp_register_script( 'nera-stw-admin-product', false, array( 'jquery' ), NERA_STW_VERSION, true );
		wp_enqueue_script( 'nera-stw-admin-product' );
		wp_add_inline_script(
			'nera-stw-admin-product',
			self::inline_js()
		);
		global $post;
		$post_id = $post ? (int) $post->ID : 0;
		wp_add_inline_script(
			'nera-stw-admin-product',
			'var neraSTWConfig = ' . wp_json_encode( array( 'postId' => $post_id ) ) . ';',
			'before'
		);
	}

	/**
	 * Inline JS for row add/remove.
	 *
	 * @return string
	 */
	private static function inline_js() {
		ob_start();
		?>
		(function($){
			function escHtml(str) {
				return String(str || '').replace(/[&<>"']/g, function (s) {
					return ({
						'&': '&amp;',
						'<': '&lt;',
						'>': '&gt;',
						'"': '&quot;',
						"'": '&#39;'
					})[s];
				});
			}

			function updateImageUi($row) {
				var imageUrl = String($row.find('.nera-stw-image-url').val() || '').trim();
				var hasImage = imageUrl !== '';
				var $preview = $row.find('.nera-stw-image-preview');
				var $img = $row.find('.nera-stw-image-thumb');
				var $upload = $row.find('.nera-stw-upload-image');
				var $remove = $row.find('.nera-stw-remove-image');

				if (hasImage) {
					$preview.removeClass('hidden');
					$img.attr('src', imageUrl);
					$remove.prop('disabled', false);
					$upload.text('<?php echo esc_js( __( 'Replace', 'nera-spin-to-win' ) ); ?>');
				} else {
					$preview.addClass('hidden');
					$img.attr('src', '');
					$remove.prop('disabled', true);
					$upload.text('<?php echo esc_js( __( 'Upload image', 'nera-spin-to-win' ) ); ?>');
				}
			}

			function rowTemplate(id) {
				var uid = id || 'seg_' + Math.random().toString(36).slice(2, 12);
				return '<tr class="nera-stw-row" data-segment-id="' + uid + '">' +
					'<td><input type="text" class="widefat nera-stw-label" placeholder="<?php echo esc_js( __( 'Label', 'nera-spin-to-win' ) ); ?>" /></td>' +
					'<td><select class="nera-stw-type">' +
						'<option value="no_win"><?php echo esc_js( __( 'Try again', 'nera-spin-to-win' ) ); ?></option>' +
						'<option value="woo_wallet"><?php echo esc_js( __( 'Site credit (wallet)', 'nera-spin-to-win' ) ); ?></option>' +
						'<option value="physical"><?php echo esc_js( __( 'Physical prize', 'nera-spin-to-win' ) ); ?></option>' +
					'</select></td>' +
					'<td><input type="number" step="0.0001" min="0.0001" class="small-text nera-stw-weight" value="1" /></td>' +
					'<td><input type="number" step="0.01" min="0" class="small-text nera-stw-wallet" placeholder="0" /></td>' +
					'<td><input type="text" class="widefat nera-stw-physical-title" placeholder="—" /></td>' +
					'<td><input type="number" min="0" class="small-text nera-stw-stock" placeholder="0" /></td>' +
					'<td>' +
						'<div class="nera-stw-image-preview hidden" style="margin-bottom:6px;">' +
							'<img class="nera-stw-image-thumb" src="" alt="' + escHtml('<?php echo esc_js( __( 'Prize image preview', 'nera-spin-to-win' ) ); ?>') + '" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #dcdcde;" />' +
						'</div>' +
						'<input type="hidden" class="nera-stw-image-id" value="" />' +
						'<input type="hidden" class="nera-stw-image-url" value="" />' +
						'<div style="display:flex;gap:6px;flex-wrap:wrap;">' +
							'<button type="button" class="button button-small nera-stw-upload-image"><?php echo esc_js( __( 'Upload image', 'nera-spin-to-win' ) ); ?></button>' +
							'<button type="button" class="button button-small nera-stw-remove-image" disabled><?php echo esc_js( __( 'Remove image', 'nera-spin-to-win' ) ); ?></button>' +
						'</div>' +
					'</td>' +
					'<td><button type="button" class="button nera-stw-remove"><?php echo esc_js( __( 'Remove', 'nera-spin-to-win' ) ); ?></button></td>' +
					'</tr>';
			}
			function exportSegments() {
				var segments = [];
				$('#nera-stw-rows tr.nera-stw-row').each(function () {
					var $r = $(this);
					var id   = $r.data('segment-id');
					var type = $r.find('.nera-stw-type').val();
					var imageId  = parseInt($r.find('.nera-stw-image-id').val(), 10) || 0;
					var imageUrl = String($r.find('.nera-stw-image-url').val() || '').trim();
					var seg = {
						id: String(id),
						label: $r.find('.nera-stw-label').val() || '',
						type: type,
						weight: parseFloat($r.find('.nera-stw-weight').val()) || 0.0001
					};
					if (imageId > 0)  { seg.image_id  = imageId; }
					if (imageUrl)     { seg.image_url = imageUrl; }
					if (type === 'woo_wallet') {
						seg.wallet_amount = parseFloat($r.find('.nera-stw-wallet').val()) || 0;
					}
					if (type === 'physical') {
						seg.physical_title = $r.find('.nera-stw-physical-title').val() || '';
						seg.stock = parseInt($r.find('.nera-stw-stock').val(), 10) || 0;
					}
					segments.push(seg);
				});

				var json = JSON.stringify(segments, null, 2);
				var blob = new Blob([json], { type: 'application/json' });
				var url  = URL.createObjectURL(blob);

				var postId = (typeof neraSTWConfig !== 'undefined' && neraSTWConfig.postId) ? neraSTWConfig.postId : 0;
				var today  = new Date();
				var yyyy   = today.getFullYear();
				var mm     = String(today.getMonth() + 1).padStart(2, '0');
				var dd     = String(today.getDate()).padStart(2, '0');
				var filename = 'spin-segments-' + postId + '-' + yyyy + '-' + mm + '-' + dd + '.json';

				var $a = $('<a>').attr('href', url).attr('download', filename).appendTo('body');
				$a[0].click();
				$a.remove();
				URL.revokeObjectURL(url);
			}

			function importSegments(file) {
				if (!file) { return; }
				var reader = new FileReader();
				reader.onload = function (e) {
					var parsed;
					try {
						parsed = JSON.parse(e.target.result);
					} catch (err) {
						alert('<?php echo esc_js( __( 'Invalid JSON file.', 'nera-spin-to-win' ) ); ?>');
						return;
					}
					if (!Array.isArray(parsed)) {
						alert('<?php echo esc_js( __( 'JSON must be an array of segment objects.', 'nera-spin-to-win' ) ); ?>');
						return;
					}

					$('#nera-stw-rows tr.nera-stw-row').remove();
					$('.nera-stw-empty').remove();

					if (parsed.length === 0) {
						$('#nera-stw-rows').append('<tr class="nera-stw-empty"><td colspan="8">' +
							escHtml('<?php echo esc_js( __( 'No segments yet. Add a row.', 'nera-spin-to-win' ) ); ?>') +
							'</td></tr>');
						return;
					}

					$.each(parsed, function (i, seg) {
						var segId = (seg.id && String(seg.id).trim() !== '')
							? String(seg.id)
							: 'seg_' + Math.random().toString(36).slice(2, 12);

						var $row = $(rowTemplate(segId));
						$('#nera-stw-rows').append($row);

						$row.find('.nera-stw-label').val(seg.label || '');
						$row.find('.nera-stw-weight').val(
							(typeof seg.weight === 'number' && seg.weight > 0) ? seg.weight : 1
						);

						var type = (seg.type === 'woo_wallet' || seg.type === 'physical') ? seg.type : 'no_win';
						$row.find('.nera-stw-type').val(type);

						if (type === 'woo_wallet') {
							$row.find('.nera-stw-wallet').val(
								(typeof seg.wallet_amount === 'number') ? seg.wallet_amount : ''
							);
						}
						if (type === 'physical') {
							$row.find('.nera-stw-physical-title').val(seg.physical_title || '');
							$row.find('.nera-stw-stock').val(
								(typeof seg.stock === 'number') ? seg.stock : 0
							);
						}

						var imageId  = (typeof seg.image_id === 'number' && seg.image_id > 0) ? seg.image_id : 0;
						var imageUrl = (typeof seg.image_url === 'string') ? seg.image_url : '';
						$row.find('.nera-stw-image-id').val(imageId || '');
						$row.find('.nera-stw-image-url').val(imageUrl);
						updateImageUi($row);
					});
				};
				reader.readAsText(file);
			}

			$(document).on('click', '#nera-stw-add-row', function(){
				$('.nera-stw-empty').remove();
				var $row = $(rowTemplate());
				$('#nera-stw-rows').append($row);
				updateImageUi($row);
			});
			$(document).on('click', '.nera-stw-remove', function(){
				$(this).closest('tr').remove();
			});
			$(document).on('click', '.nera-stw-upload-image', function(e){
				e.preventDefault();
				var $row = $(this).closest('tr');
				var frame = wp.media({
					title: '<?php echo esc_js( __( 'Select prize image', 'nera-spin-to-win' ) ); ?>',
					button: { text: '<?php echo esc_js( __( 'Use image', 'nera-spin-to-win' ) ); ?>' },
					library: { type: 'image' },
					multiple: false
				});

				frame.on('select', function(){
					var attachment = frame.state().get('selection').first().toJSON();
					var imageUrl = '';
					if (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url) {
						imageUrl = attachment.sizes.medium.url;
					} else if (attachment.url) {
						imageUrl = attachment.url;
					}
					$row.find('.nera-stw-image-id').val(parseInt(attachment.id, 10) || 0);
					$row.find('.nera-stw-image-url').val(imageUrl || '');
					updateImageUi($row);
				});

				frame.open();
			});
			$(document).on('click', '.nera-stw-remove-image', function(e){
				e.preventDefault();
				var $row = $(this).closest('tr');
				$row.find('.nera-stw-image-id').val('');
				$row.find('.nera-stw-image-url').val('');
				updateImageUi($row);
			});
			$(document).on('click', '#nera-stw-export-json', function () {
				exportSegments();
			});
			$(document).on('click', '#nera-stw-import-json', function () {
				$('#nera-stw-import-file').val('').trigger('click');
			});
			$(document).on('change', '#nera-stw-import-file', function () {
				importSegments(this.files[0]);
				$(this).val('');
			});
			$('form#post').on('submit', function(){
				var segments = [];
				$('#nera-stw-rows tr.nera-stw-row').each(function(){
					var $r = $(this);
					var id = $r.data('segment-id');
					var type = $r.find('.nera-stw-type').val();
					var imageId = parseInt($r.find('.nera-stw-image-id').val(), 10) || 0;
					var imageUrl = String($r.find('.nera-stw-image-url').val() || '').trim();
					var seg = {
						id: String(id),
						label: $r.find('.nera-stw-label').val() || '',
						type: type,
						weight: parseFloat($r.find('.nera-stw-weight').val()) || 0.0001
					};
					if (imageId > 0) {
						seg.image_id = imageId;
					}
					if (imageUrl) {
						seg.image_url = imageUrl;
					}
					if (type === 'woo_wallet') {
						seg.wallet_amount = parseFloat($r.find('.nera-stw-wallet').val()) || 0;
					}
					if (type === 'physical') {
						seg.physical_title = $r.find('.nera-stw-physical-title').val() || '';
						seg.stock = parseInt($r.find('.nera-stw-stock').val(), 10) || 0;
					}
					segments.push(seg);
				});
				$('#nera_stw_segments_json').val(JSON.stringify(segments));
			});
			$('#nera-stw-rows tr.nera-stw-row').each(function(){
				updateImageUi($(this));
			});
		})(jQuery);
		<?php
		return ob_get_clean();
	}

	/**
	 * Panel HTML.
	 */
	public static function panel() {
		global $post;

		$enabled = get_post_meta( $post->ID, Nera_STW_Product_Meta::META_ENABLED, true );
		$json    = get_post_meta( $post->ID, Nera_STW_Product_Meta::META_SEGMENTS, true );
		$rows    = array();
		if ( is_string( $json ) && '' !== $json ) {
			$decoded = json_decode( $json, true );
			if ( is_array( $decoded ) ) {
				$rows = $decoded;
			}
		}
		?>
		<div id="nera_spin_to_win_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<p class="form-field">
					<label for="_nera_stw_enabled"><?php esc_html_e( 'Enable Spin To Win', 'nera-spin-to-win' ); ?></label>
					<input type="checkbox" name="_nera_stw_enabled" id="_nera_stw_enabled" value="yes" <?php checked( $enabled, 'yes' ); ?> />
					<span class="description"><?php esc_html_e( 'Grant spins from ticket quantity and show the wheel for this competition.', 'nera-spin-to-win' ); ?></span>
				</p>
				<p class="form-field">
					<label><?php esc_html_e( 'Wheel segments', 'nera-spin-to-win' ); ?></label>
					<span class="description"><?php esc_html_e( 'Weights control relative probability. Physical prizes use stock; site credit is unlimited.', 'nera-spin-to-win' ); ?></span>
				</p>
				<table class="widefat" style="max-width: 1200px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Label', 'nera-spin-to-win' ); ?></th>
							<th><?php esc_html_e( 'Type', 'nera-spin-to-win' ); ?></th>
							<th><?php esc_html_e( 'Weight', 'nera-spin-to-win' ); ?></th>
							<th><?php esc_html_e( 'Credit amount', 'nera-spin-to-win' ); ?></th>
							<th><?php esc_html_e( 'Physical title', 'nera-spin-to-win' ); ?></th>
							<th><?php esc_html_e( 'Stock', 'nera-spin-to-win' ); ?></th>
							<th><?php esc_html_e( 'Image', 'nera-spin-to-win' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id="nera-stw-rows">
						<?php
						if ( empty( $rows ) ) {
							echo '<tr class="nera-stw-empty"><td colspan="8">' . esc_html__( 'No segments yet. Add a row.', 'nera-spin-to-win' ) . '</td></tr>';
						} else {
							foreach ( $rows as $r ) {
								$id        = isset( $r['id'] ) ? esc_attr( $r['id'] ) : 'seg_' . wp_generate_password( 8, false );
								$label     = isset( $r['label'] ) ? esc_attr( $r['label'] ) : '';
								$type      = isset( $r['type'] ) ? esc_attr( $r['type'] ) : 'no_win';
								$w         = isset( $r['weight'] ) ? esc_attr( (string) $r['weight'] ) : '1';
								$wa        = isset( $r['wallet_amount'] ) ? esc_attr( (string) $r['wallet_amount'] ) : '';
								$pt        = isset( $r['physical_title'] ) ? esc_attr( $r['physical_title'] ) : '';
								$st        = isset( $r['stock'] ) ? esc_attr( (string) $r['stock'] ) : '0';
								$image_id  = isset( $r['image_id'] ) ? absint( $r['image_id'] ) : 0;
								$image_url = '';
								if ( $image_id > 0 ) {
									$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
								}
								if ( ! $image_url && ! empty( $r['image_url'] ) ) {
									$image_url = esc_url_raw( (string) $r['image_url'] );
								}
								?>
								<tr class="nera-stw-row" data-segment-id="<?php echo esc_attr( $id ); ?>">
									<td><input type="text" class="widefat nera-stw-label" value="<?php echo esc_attr( $label ); ?>" /></td>
									<td>
										<select class="nera-stw-type">
											<option value="no_win" <?php selected( $type, 'no_win' ); ?>><?php esc_html_e( 'Try again', 'nera-spin-to-win' ); ?></option>
											<option value="woo_wallet" <?php selected( $type, 'woo_wallet' ); ?>><?php esc_html_e( 'Site credit', 'nera-spin-to-win' ); ?></option>
											<option value="physical" <?php selected( $type, 'physical' ); ?>><?php esc_html_e( 'Physical', 'nera-spin-to-win' ); ?></option>
										</select>
									</td>
									<td><input type="number" step="0.0001" min="0.0001" class="small-text nera-stw-weight" value="<?php echo esc_attr( $w ); ?>" /></td>
									<td><input type="number" step="0.01" min="0" class="small-text nera-stw-wallet" value="<?php echo esc_attr( $wa ); ?>" /></td>
									<td><input type="text" class="widefat nera-stw-physical-title" value="<?php echo esc_attr( $pt ); ?>" /></td>
									<td><input type="number" min="0" class="small-text nera-stw-stock" value="<?php echo esc_attr( $st ); ?>" /></td>
									<td>
										<div class="nera-stw-image-preview<?php echo $image_url ? '' : ' hidden'; ?>" style="margin-bottom:6px;">
											<img
												class="nera-stw-image-thumb"
												src="<?php echo esc_url( $image_url ); ?>"
												alt="<?php esc_attr_e( 'Prize image preview', 'nera-spin-to-win' ); ?>"
												style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #dcdcde;"
											/>
										</div>
										<input type="hidden" class="nera-stw-image-id" value="<?php echo esc_attr( (string) $image_id ); ?>" />
										<input type="hidden" class="nera-stw-image-url" value="<?php echo esc_attr( $image_url ); ?>" />
										<div style="display:flex;gap:6px;flex-wrap:wrap;">
											<button type="button" class="button button-small nera-stw-upload-image">
												<?php echo $image_url ? esc_html__( 'Replace', 'nera-spin-to-win' ) : esc_html__( 'Upload image', 'nera-spin-to-win' ); ?>
											</button>
											<button type="button" class="button button-small nera-stw-remove-image" <?php disabled( ! $image_url ); ?>>
												<?php esc_html_e( 'Remove image', 'nera-spin-to-win' ); ?>
											</button>
										</div>
									</td>
									<td><button type="button" class="button nera-stw-remove"><?php esc_html_e( 'Remove', 'nera-spin-to-win' ); ?></button></td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>
				<p style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
					<button type="button" class="button" id="nera-stw-add-row"><?php esc_html_e( 'Add segment', 'nera-spin-to-win' ); ?></button>
					<button type="button" class="button" id="nera-stw-export-json"><?php esc_html_e( 'Export JSON', 'nera-spin-to-win' ); ?></button>
					<button type="button" class="button" id="nera-stw-import-json"><?php esc_html_e( 'Import JSON', 'nera-spin-to-win' ); ?></button>
					<input type="file" id="nera-stw-import-file" accept=".json,application/json" style="display:none;" />
				</p>
				<input type="hidden" name="nera_stw_segments_json" id="nera_stw_segments_json" value="<?php echo esc_attr( is_string( $json ) ? $json : '' ); ?>" />
			</div>
		</div>
		<?php
	}

	/**
	 * Save handler.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		$enabled = isset( $_POST['_nera_stw_enabled'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, Nera_STW_Product_Meta::META_ENABLED, $enabled );

		$raw_json = isset( $_POST['nera_stw_segments_json'] ) ? wp_unslash( $_POST['nera_stw_segments_json'] ) : '[]';
		$decoded  = json_decode( $raw_json, true );
		if ( ! is_array( $decoded ) ) {
			$decoded = array();
		}

		$segments = array();
		foreach ( $decoded as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id = isset( $row['id'] ) ? sanitize_text_field( (string) $row['id'] ) : 'seg_' . wp_generate_password( 10, false );
			if ( '' === $id ) {
				$id = 'seg_' . wp_generate_password( 10, false );
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
				'label'  => isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '',
				'type'   => $type,
				'weight' => $weight,
			);
			if ( '' === $seg['label'] ) {
				$seg['label'] = $id;
			}
			$seg['image_id']  = isset( $row['image_id'] ) ? absint( $row['image_id'] ) : 0;
			$seg['image_url'] = isset( $row['image_url'] ) ? esc_url_raw( (string) $row['image_url'] ) : '';

			if ( $seg['image_id'] < 1 ) {
				unset( $seg['image_id'] );
			}
			if ( '' === $seg['image_url'] ) {
				unset( $seg['image_url'] );
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
			$segments[] = $seg;
		}

		update_post_meta( $post_id, Nera_STW_Product_Meta::META_SEGMENTS, wp_json_encode( $segments ) );

		Nera_STW_Segment_Stock::sync_initial_from_segments( $post_id, Nera_STW_Product_Meta::get_segments( $post_id ) );
	}
}
