<?php
/**
 * Per-content FAQ meta box.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register FAQ meta boxes.
 *
 * @param string $post_type Current post type.
 * @return void
 */
function sas_register_faq_meta_boxes( $post_type ) {
	$settings = sas_get_settings();

	if ( empty( $settings['settings']['enable_faq_meta_box'] ) || ! in_array( $post_type, sas_get_enabled_post_types( 'faq' ), true ) ) {
		return;
	}

	add_meta_box(
		'sas_faq_items',
		__( 'FAQ', 'seo-and-social' ),
		'sas_render_faq_meta_box',
		$post_type,
		'normal',
		'high'
	);
}

/**
 * Render FAQ meta box.
 *
 * @param WP_Post $post Post.
 * @return void
 */
function sas_render_faq_meta_box( $post ) {
	$items = sas_get_faq_items( $post->ID );
	wp_nonce_field( 'sas_save_faq_items', 'sas_faq_nonce' );
	?>
	<div class="sas-meta-box">
		<div class="notice notice-info inline">
			<p><?php echo esc_html__( 'FAQ is per content item. Use the editor for simple formatting. Only enabled rows are exposed in REST, sorted by position.', 'seo-and-social' ); ?></p>
		</div>

		<div class="sas-repeatable" data-sas-repeater="faq-items">
			<div class="sas-repeatable-rows" data-sas-rows>
				<?php foreach ( $items as $index => $item ) : ?>
					<?php sas_render_faq_row( (string) $index, $item ); ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button" data-sas-add-row data-template="tmpl-sas-faq-row">
				<?php echo esc_html__( 'Add FAQ item', 'seo-and-social' ); ?>
			</button>
		</div>
		<script type="text/template" id="tmpl-sas-faq-row">
			<?php sas_render_faq_row( '__INDEX__', array() ); ?>
		</script>
	</div>
	<?php
}

/**
 * Render FAQ row.
 *
 * @param string $index Row index.
 * @param array  $item Row data.
 * @return void
 */
function sas_render_faq_row( $index, $item ) {
	$item = wp_parse_args(
		$item,
		array(
			'question' => '',
			'answer' => '',
			'enabled' => true,
			'position' => '',
		)
	);
	$label = $item['question'] !== '' ? $item['question'] : __( 'New question', 'seo-and-social' );
	$editor_id = 'sas_faq_answer_' . sanitize_key( (string) $index );
	?>
	<div class="sas-row sas-faq-row is-collapsed">
		<button type="button" class="sas-faq-row-header" data-sas-toggle-faq-row>
			<strong class="sas-faq-row-title"><?php echo esc_html( $label ); ?></strong>
			<span class="sas-faq-row-toggle"><?php echo esc_html__( 'Open', 'seo-and-social' ); ?></span>
		</button>

		<div class="sas-faq-row-body">
			<p>
				<label for="<?php echo esc_attr( $editor_id . '_question' ); ?>"><strong><?php echo esc_html__( 'Question', 'seo-and-social' ); ?></strong></label>
				<input
					type="text"
					id="<?php echo esc_attr( $editor_id . '_question' ); ?>"
					name="sas_faq[<?php echo esc_attr( $index ); ?>][question]"
					value="<?php echo esc_attr( $item['question'] ); ?>"
					class="widefat sas-faq-title-input"
				>
			</p>

			<p>
				<label for="<?php echo esc_attr( $editor_id ); ?>"><strong><?php echo esc_html__( 'Answer', 'seo-and-social' ); ?></strong></label>
				<textarea
					id="<?php echo esc_attr( $editor_id ); ?>"
					name="sas_faq[<?php echo esc_attr( $index ); ?>][answer]"
					rows="5"
					class="widefat sas-faq-answer-editor"
				><?php echo esc_textarea( $item['answer'] ); ?></textarea>
			</p>

			<div class="sas-faq-row-options">
				<label>
					<span><?php echo esc_html__( 'Position', 'seo-and-social' ); ?></span>
					<input type="number" min="0" step="1" name="sas_faq[<?php echo esc_attr( $index ); ?>][position]" value="<?php echo esc_attr( (string) $item['position'] ); ?>">
				</label>
				<label class="sas-inline-check">
					<input type="checkbox" name="sas_faq[<?php echo esc_attr( $index ); ?>][enabled]" value="1" <?php checked( $item['enabled'] ); ?>>
					<?php echo esc_html__( 'Enabled', 'seo-and-social' ); ?>
				</label>
			</div>

			<button type="button" class="button-link-delete" data-sas-remove-row><?php echo esc_html__( 'Remove question', 'seo-and-social' ); ?></button>
		</div>
	</div>
	<?php
}

/**
 * Save FAQ meta box.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function sas_save_faq_meta_box( $post_id ) {
	if (
		! isset( $_POST['sas_faq_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sas_faq_nonce'] ) ), 'sas_save_faq_items' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! in_array( get_post_type( $post_id ), sas_get_enabled_post_types( 'faq' ), true ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The raw FAQ array is sanitized field-by-field by sas_sanitize_faq_items().
	$raw = isset( $_POST['sas_faq'] ) ? wp_unslash( $_POST['sas_faq'] ) : array();
	update_post_meta( $post_id, SAS_FAQ_META_KEY, sas_sanitize_faq_items( $raw ) );
}

/**
 * Get FAQ items.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function sas_get_faq_items( $post_id ) {
	$saved = get_post_meta( $post_id, SAS_FAQ_META_KEY, true );

	return is_array( $saved ) ? $saved : array();
}

/**
 * Sanitize FAQ items.
 *
 * @param mixed $items Raw items.
 * @return array
 */
function sas_sanitize_faq_items( $items ) {
	if ( ! is_array( $items ) ) {
		return array();
	}

	$settings = sas_get_settings();
	$allow_html = ! empty( $settings['settings']['faq_allow_html'] );
	$clean = array();
	$fallback_position = 1;

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$question = isset( $item['question'] ) ? sanitize_text_field( $item['question'] ) : '';
		$answer = isset( $item['answer'] ) ? (string) $item['answer'] : '';
		$answer = $allow_html ? wp_kses_post( $answer ) : sas_sanitize_textarea( $answer );

		if ( $question === '' && $answer === '' ) {
			continue;
		}

		$position = isset( $item['position'] ) && $item['position'] !== '' ? absint( $item['position'] ) : $fallback_position;

		$clean[] = array(
			'question' => $question,
			'answer' => $answer,
			'enabled' => ! empty( $item['enabled'] ),
			'position' => $position,
		);

		$fallback_position++;
	}

	return $clean;
}

/**
 * Public FAQ items.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function sas_get_public_faq_items( $post_id ) {
	$items = array_filter(
		sas_get_faq_items( $post_id ),
		static function ( $item ) {
			return is_array( $item ) && ! empty( $item['enabled'] ) && ! empty( $item['question'] ) && ! empty( $item['answer'] );
		}
	);

	usort(
		$items,
		static function ( $a, $b ) {
			return ( (int) $a['position'] ) <=> ( (int) $b['position'] );
		}
	);

	return array_values( $items );
}
