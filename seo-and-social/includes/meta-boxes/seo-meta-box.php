<?php
/**
 * Per-content SEO meta box.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register SEO meta boxes.
 *
 * @param string $post_type Current post type.
 * @return void
 */
function sas_register_seo_meta_boxes( $post_type ) {
	$settings = sas_get_settings();

	if ( empty( $settings['settings']['enable_seo_meta_box'] ) || ! in_array( $post_type, sas_get_enabled_post_types( 'seo' ), true ) ) {
		return;
	}

	add_meta_box(
		'sas_seo_overrides',
		__( 'SEO', 'seo-and-social' ),
		'sas_render_seo_meta_box',
		$post_type,
		'normal',
		'default'
	);
}

/**
 * Render SEO meta box.
 *
 * @param WP_Post $post Post.
 * @return void
 */
function sas_render_seo_meta_box( $post ) {
	$seo = sas_get_post_seo_overrides( $post->ID );
	$og_image_url = $seo['og_image_url'];

	if ( ! $og_image_url && $seo['og_image_id'] ) {
		$attachment_url = wp_get_attachment_image_url( (int) $seo['og_image_id'], 'full' );
		$og_image_url = $attachment_url ? $attachment_url : '';
	}

	wp_nonce_field( 'sas_save_seo_overrides', 'sas_seo_nonce' );
	?>
	<div class="sas-meta-box">
		<p class="description"><?php echo esc_html__( 'Fill in only the fields that should override global Seo & Social settings.', 'seo-and-social' ); ?></p>

		<?php sas_meta_text_field( 'sas_seo[seo_title]', 'sas_seo_title', __( 'SEO title', 'seo-and-social' ), $seo['seo_title'], __( 'Overrides the frontend SEO title for this content item.', 'seo-and-social' ) ); ?>
		<?php sas_meta_textarea_field( 'sas_seo[seo_description]', 'sas_seo_description', __( 'SEO description', 'seo-and-social' ), $seo['seo_description'], __( 'Overrides the frontend SEO description for this content item.', 'seo-and-social' ) ); ?>

		<div class="sas-field">
			<div class="sas-label-row">
				<label for="sas_seo_og_image_url"><?php echo esc_html__( 'OG image', 'seo-and-social' ); ?></label>
				<?php sas_info_button( 'sas_seo_og_image_url', __( 'Image for social sharing. The frontend decides how to render it.', 'seo-and-social' ) ); ?>
			</div>
			<input type="hidden" id="sas_seo_og_image_id" name="sas_seo[og_image_id]" value="<?php echo esc_attr( (string) $seo['og_image_id'] ); ?>">
			<div class="sas-input-row">
				<input type="url" id="sas_seo_og_image_url" name="sas_seo[og_image_url]" value="<?php echo esc_attr( $og_image_url ); ?>" class="regular-text" data-sas-media-linked-id="sas_seo_og_image_id">
				<button type="button" class="button" data-sas-media-id-target="sas_seo_og_image_id" data-sas-media-url-target="sas_seo_og_image_url">
					<?php echo esc_html__( 'Choose image', 'seo-and-social' ); ?>
				</button>
			</div>
			<?php sas_info_panel( 'sas_seo_og_image_url', __( 'Image for social sharing. The frontend decides how to render it.', 'seo-and-social' ) ); ?>
		</div>

		<?php sas_meta_text_field( 'sas_seo[canonical_url]', 'sas_seo_canonical_url', __( 'Canonical URL', 'seo-and-social' ), $seo['canonical_url'], __( 'Optional canonical URL override.', 'seo-and-social' ), 'url' ); ?>

		<div class="sas-field">
			<div class="sas-label-row">
				<label for="sas_seo_robots"><?php echo esc_html__( 'Robots', 'seo-and-social' ); ?></label>
				<?php sas_info_button( 'sas_seo_robots', __( 'Leave empty to let the frontend use its default robots behavior.', 'seo-and-social' ) ); ?>
			</div>
			<select id="sas_seo_robots" name="sas_seo[robots]">
				<?php foreach ( sas_get_robots_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $seo['robots'], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php sas_info_panel( 'sas_seo_robots', __( 'Leave empty to let the frontend use its default robots behavior.', 'seo-and-social' ) ); ?>
		</div>

		<?php sas_meta_text_field( 'sas_seo[schema_type]', 'sas_seo_schema_type', __( 'Schema type', 'seo-and-social' ), $seo['schema_type'], __( 'Schema.org type for this content item, such as WebPage, AboutPage, ContactPage, or FAQPage.', 'seo-and-social' ) ); ?>
		<?php sas_meta_textarea_field( 'sas_seo[custom_schema_json]', 'sas_seo_custom_schema_json', __( 'Custom schema JSON', 'seo-and-social' ), $seo['custom_schema_json'], __( 'Optional valid JSON for advanced frontend schema handling. Invalid JSON is not exposed.', 'seo-and-social' ), 6 ); ?>
		<p class="sas-warning-note"><?php echo esc_html__( 'Invalid JSON is automatically deleted and will not be saved, to prevent breaking the API.', 'seo-and-social' ); ?></p>
	</div>
	<?php
}

/**
 * Save SEO meta box.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function sas_save_seo_meta_box( $post_id ) {
	if (
		! isset( $_POST['sas_seo_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sas_seo_nonce'] ) ), 'sas_save_seo_overrides' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! in_array( get_post_type( $post_id ), sas_get_enabled_post_types( 'seo' ), true ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The raw SEO array is sanitized field-by-field by sas_sanitize_post_seo_overrides().
	$raw = isset( $_POST['sas_seo'] ) ? wp_unslash( $_POST['sas_seo'] ) : array();
	$seo = sas_sanitize_post_seo_overrides( $raw );
	update_post_meta( $post_id, SAS_SEO_META_KEY, $seo );

	if ( ! empty( $seo['og_image_id'] ) ) {
		sas_get_optimized_og_image( (int) $seo['og_image_id'], true );
	}
}

/**
 * Default SEO overrides.
 *
 * @return array
 */
function sas_get_default_post_seo_overrides() {
	return array(
		'seo_title' => '',
		'seo_description' => '',
		'og_image_id' => 0,
		'og_image_url' => '',
		'canonical_url' => '',
		'robots' => '',
		'schema_type' => '',
		'custom_schema_json' => '',
	);
}

/**
 * Get stored SEO overrides.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function sas_get_post_seo_overrides( $post_id ) {
	$saved = get_post_meta( $post_id, SAS_SEO_META_KEY, true );

	return is_array( $saved ) ? sas_merge_settings( sas_get_default_post_seo_overrides(), $saved ) : sas_get_default_post_seo_overrides();
}

/**
 * Sanitize SEO overrides.
 *
 * @param mixed $raw Raw SEO data.
 * @return array
 */
function sas_sanitize_post_seo_overrides( $raw ) {
	$raw = is_array( $raw ) ? $raw : array();
	$robots = isset( $raw['robots'] ) ? sanitize_text_field( $raw['robots'] ) : '';
	$allowed_robots = array_keys( sas_get_robots_options() );

	if ( ! in_array( $robots, $allowed_robots, true ) ) {
		$robots = '';
	}

	$og_image_id = isset( $raw['og_image_id'] ) ? absint( $raw['og_image_id'] ) : 0;

	return array(
		'seo_title' => isset( $raw['seo_title'] ) ? sanitize_text_field( $raw['seo_title'] ) : '',
		'seo_description' => isset( $raw['seo_description'] ) ? sas_sanitize_textarea( $raw['seo_description'] ) : '',
		'og_image_id' => $og_image_id,
		'og_image_url' => isset( $raw['og_image_url'] ) ? esc_url_raw( $raw['og_image_url'] ) : '',
		'canonical_url' => isset( $raw['canonical_url'] ) ? esc_url_raw( $raw['canonical_url'] ) : '',
		'robots' => $robots,
		'schema_type' => isset( $raw['schema_type'] ) ? sanitize_text_field( $raw['schema_type'] ) : '',
		'custom_schema_json' => isset( $raw['custom_schema_json'] ) ? sas_sanitize_json_string( $raw['custom_schema_json'] ) : '',
	);
}

/**
 * Public SEO override payload.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function sas_get_public_post_seo_overrides( $post_id ) {
	$seo = sas_get_post_seo_overrides( $post_id );
	$seo['og_image_original_url'] = $seo['og_image_url'];
	$seo['og_image_optimized'] = null;

	if ( $seo['og_image_id'] ) {
		$attachment_url = wp_get_attachment_image_url( (int) $seo['og_image_id'], 'full' );
		$original_url = $attachment_url ? $attachment_url : $seo['og_image_url'];
		$optimized = sas_get_optimized_og_image( (int) $seo['og_image_id'] );

		$seo['og_image_original_url'] = $original_url;
		$seo['og_image_url'] = $original_url;

		if ( $optimized ) {
			$seo['og_image_optimized'] = array(
				'url' => $optimized['url'],
				'width' => (int) $optimized['width'],
				'height' => (int) $optimized['height'],
				'mime' => $optimized['mime'],
			);
			$seo['og_image_url'] = $optimized['url'];
		}
	}

	return $seo;
}

/**
 * Public resolved SEO payload for a content item.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function sas_get_resolved_post_seo( $post_id ) {
	$settings = sas_get_public_settings();
	$global_seo = $settings['seo'];
	$overrides = sas_get_public_post_seo_overrides( $post_id );

	return array(
		'seo_title' => $overrides['seo_title'] ? $overrides['seo_title'] : $global_seo['default_meta_title'],
		'seo_description' => $overrides['seo_description'] ? $overrides['seo_description'] : $global_seo['default_meta_description'],
		'og_image_id' => $overrides['og_image_id'] ? $overrides['og_image_id'] : (int) $global_seo['default_og_image_id'],
		'og_image_url' => $overrides['og_image_url'] ? $overrides['og_image_url'] : $global_seo['default_og_image'],
		'og_image_original_url' => $overrides['og_image_original_url'] ? $overrides['og_image_original_url'] : $global_seo['default_og_image_original_url'],
		'og_image_optimized' => $overrides['og_image_optimized'] ? $overrides['og_image_optimized'] : $global_seo['default_og_image_optimized'],
		'canonical_url' => $overrides['canonical_url'],
		'robots' => $overrides['robots'] ? $overrides['robots'] : $global_seo['default_robots'],
		'schema_type' => $overrides['schema_type'] ? $overrides['schema_type'] : $global_seo['schema_type'],
		'custom_schema_json' => $overrides['custom_schema_json'] ? $overrides['custom_schema_json'] : $global_seo['custom_schema_json'],
		'source' => array(
			'seo_title' => $overrides['seo_title'] ? 'override' : 'global',
			'seo_description' => $overrides['seo_description'] ? 'override' : 'global',
			'og_image' => $overrides['og_image_url'] ? 'override' : 'global',
			'schema_type' => $overrides['schema_type'] ? 'override' : 'global',
			'custom_schema_json' => $overrides['custom_schema_json'] ? 'override' : 'global',
		),
	);
}

/**
 * Robots options.
 *
 * @return array
 */
function sas_get_robots_options() {
	return array(
		'' => __( 'empty/fallback', 'seo-and-social' ),
		'index,follow' => 'index,follow',
		'noindex,follow' => 'noindex,follow',
		'index,nofollow' => 'index,nofollow',
		'noindex,nofollow' => 'noindex,nofollow',
	);
}

/**
 * Render meta text field.
 *
 * @param string $name Name.
 * @param string $id ID.
 * @param string $label Label.
 * @param string $value Value.
 * @param string $info Info.
 * @param string $type Input type.
 * @return void
 */
function sas_meta_text_field( $name, $id, $label, $value, $info = '', $type = 'text' ) {
	?>
	<div class="sas-field">
		<div class="sas-label-row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php sas_info_button( $id, $info ); ?>
		</div>
		<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<?php sas_info_panel( $id, $info ); ?>
	</div>
	<?php
}

/**
 * Render meta textarea field.
 *
 * @param string $name Name.
 * @param string $id ID.
 * @param string $label Label.
 * @param string $value Value.
 * @param string $info Info.
 * @param int    $rows Rows.
 * @return void
 */
function sas_meta_textarea_field( $name, $id, $label, $value, $info = '', $rows = 3 ) {
	?>
	<div class="sas-field">
		<div class="sas-label-row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php sas_info_button( $id, $info ); ?>
		</div>
		<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="<?php echo esc_attr( (string) $rows ); ?>" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
		<?php sas_info_panel( $id, $info ); ?>
	</div>
	<?php
}
