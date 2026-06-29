<?php
/**
 * OG image optimization helpers.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAS_OG_IMAGE_SIZE', 'sas_og_image' );
define( 'SAS_OG_IMAGE_WIDTH', 1200 );
define( 'SAS_OG_IMAGE_HEIGHT', 630 );
define( 'SAS_OG_IMAGE_META_KEY', '_sas_og_image_1200x630' );

/**
 * Register the plugin OG image size with WordPress.
 *
 * @return void
 */
function sas_register_og_image_size() {
	add_image_size( SAS_OG_IMAGE_SIZE, SAS_OG_IMAGE_WIDTH, SAS_OG_IMAGE_HEIGHT, true );
}

/**
 * Check whether OG image optimization is enabled.
 *
 * @return bool
 */
function sas_is_og_image_optimization_enabled() {
	$settings = sas_get_settings();

	return ! empty( $settings['settings']['enable_og_image_optimization'] );
}

/**
 * Get optimized OG image metadata for an attachment.
 *
 * @param int  $attachment_id Attachment ID.
 * @param bool $generate Whether to generate the image if missing.
 * @return array|null
 */
function sas_get_optimized_og_image( $attachment_id, $generate = false ) {
	$attachment_id = absint( $attachment_id );

	if ( ! $attachment_id || ! sas_is_og_image_optimization_enabled() ) {
		return null;
	}

	$meta = get_post_meta( $attachment_id, SAS_OG_IMAGE_META_KEY, true );

	if ( is_array( $meta ) && ! empty( $meta['path'] ) && file_exists( $meta['path'] ) ) {
		return $meta;
	}

	if ( ! $generate ) {
		return null;
	}

	$generated = sas_generate_optimized_og_image( $attachment_id );

	return is_wp_error( $generated ) ? null : $generated;
}

/**
 * Generate a 1200x630 WebP cover-cropped OG image for an attachment.
 *
 * @param int $attachment_id Attachment ID.
 * @return array|WP_Error
 */
function sas_generate_optimized_og_image( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	$source_path = get_attached_file( $attachment_id );

	if ( ! $attachment_id || ! $source_path || ! file_exists( $source_path ) ) {
		return new WP_Error( 'sas_missing_source_image', __( 'Source image file is missing.', 'seo-and-social' ) );
	}

	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return new WP_Error( 'sas_webp_not_supported', __( 'This server does not support WebP generation.', 'seo-and-social' ) );
	}

	$editor = wp_get_image_editor( $source_path );

	if ( is_wp_error( $editor ) ) {
		return $editor;
	}

	$resize_result = $editor->resize( SAS_OG_IMAGE_WIDTH, SAS_OG_IMAGE_HEIGHT, true );

	if ( is_wp_error( $resize_result ) ) {
		return $resize_result;
	}

	$editor->set_quality( 82 );

	$destination_path = sas_get_optimized_og_image_path( $attachment_id, $source_path );
	sas_delete_optimized_og_image( $attachment_id );

	$saved = $editor->save( $destination_path, 'image/webp' );

	if ( is_wp_error( $saved ) ) {
		return $saved;
	}

	$uploads = wp_upload_dir();
	$relative_path = ltrim( str_replace( wp_normalize_path( $uploads['basedir'] ), '', wp_normalize_path( $destination_path ) ), '/' );
	$url = trailingslashit( $uploads['baseurl'] ) . str_replace( '\\', '/', $relative_path );

	$meta = array(
		'path' => $destination_path,
		'url' => esc_url_raw( $url ),
		'width' => SAS_OG_IMAGE_WIDTH,
		'height' => SAS_OG_IMAGE_HEIGHT,
		'mime' => 'image/webp',
		'source_id' => $attachment_id,
		'generated_at' => time(),
	);

	update_post_meta( $attachment_id, SAS_OG_IMAGE_META_KEY, $meta );

	return $meta;
}

/**
 * Get destination path for the optimized OG image.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $source_path Source path.
 * @return string
 */
function sas_get_optimized_og_image_path( $attachment_id, $source_path ) {
	$path_info = pathinfo( $source_path );
	$filename = sanitize_file_name( $path_info['filename'] . '-sas-og-' . $attachment_id . '-' . SAS_OG_IMAGE_WIDTH . 'x' . SAS_OG_IMAGE_HEIGHT . '.webp' );

	return trailingslashit( $path_info['dirname'] ) . $filename;
}

/**
 * Delete the optimized OG image for an attachment.
 *
 * @param int $attachment_id Attachment ID.
 * @return bool
 */
function sas_delete_optimized_og_image( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	$meta = get_post_meta( $attachment_id, SAS_OG_IMAGE_META_KEY, true );
	$deleted = false;

	if ( is_array( $meta ) && ! empty( $meta['path'] ) && sas_is_optimized_og_image_path( $meta['path'] ) && file_exists( $meta['path'] ) ) {
		wp_delete_file( $meta['path'] );
		$deleted = true;
	}

	delete_post_meta( $attachment_id, SAS_OG_IMAGE_META_KEY );

	return $deleted;
}

/**
 * Check that a generated OG image path belongs to this plugin and uploads directory.
 *
 * @param string $path File path.
 * @return bool
 */
function sas_is_optimized_og_image_path( $path ) {
	$uploads = wp_upload_dir();
	$base_dir = isset( $uploads['basedir'] ) ? wp_normalize_path( $uploads['basedir'] ) : '';
	$path = wp_normalize_path( (string) $path );
	$filename = basename( $path );

	if ( ! $base_dir || strpos( $path, trailingslashit( $base_dir ) ) !== 0 ) {
		return false;
	}

	return (bool) preg_match( '/-sas-og-\d+-' . SAS_OG_IMAGE_WIDTH . 'x' . SAS_OG_IMAGE_HEIGHT . '\.webp$/', $filename );
}

/**
 * Delete all optimized OG images generated by this plugin.
 *
 * @return int
 */
function sas_delete_all_optimized_og_images() {
	$attachment_ids = sas_get_all_optimized_og_attachment_ids();
	$deleted = 0;

	foreach ( $attachment_ids as $attachment_id ) {
		if ( sas_delete_optimized_og_image( $attachment_id ) ) {
			$deleted++;
		}
	}

	return $deleted;
}

/**
 * Get all attachment IDs that have optimized OG image metadata.
 *
 * @return array
 */
function sas_get_all_optimized_og_attachment_ids() {
	global $wpdb;

	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
			SAS_OG_IMAGE_META_KEY
		)
	);

	return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
}

/**
 * Regenerate optimized OG images for all attachment IDs currently used by the plugin.
 *
 * @return array
 */
function sas_regenerate_all_og_images() {
	$attachment_ids = sas_get_used_og_attachment_ids();
	$result = array(
		'generated' => 0,
		'failed' => 0,
		'skipped' => 0,
	);

	foreach ( $attachment_ids as $attachment_id ) {
		if ( ! sas_is_og_image_optimization_enabled() ) {
			$result['skipped']++;
			continue;
		}

		$generated = sas_generate_optimized_og_image( $attachment_id );

		if ( is_wp_error( $generated ) ) {
			$result['failed']++;
		} else {
			$result['generated']++;
		}
	}

	return $result;
}

/**
 * Get attachment IDs used by global and per-content OG image fields.
 *
 * @return array
 */
function sas_get_used_og_attachment_ids() {
	global $wpdb;

	$ids = array();
	$settings = sas_get_settings();

	if ( ! empty( $settings['seo']['default_og_image_id'] ) ) {
		$ids[] = absint( $settings['seo']['default_og_image_id'] );
	}

	$rows = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
			SAS_SEO_META_KEY
		)
	);

	foreach ( (array) $rows as $row ) {
		$seo = maybe_unserialize( $row );

		if ( is_array( $seo ) && ! empty( $seo['og_image_id'] ) ) {
			$ids[] = absint( $seo['og_image_id'] );
		}
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}
