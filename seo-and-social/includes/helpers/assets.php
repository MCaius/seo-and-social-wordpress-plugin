<?php
/**
 * Admin asset loading.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue admin assets where the plugin UI appears.
 *
 * @param string $hook_suffix Current admin page.
 * @return void
 */
function sas_enqueue_admin_assets( $hook_suffix ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$is_settings_page = $hook_suffix === 'toplevel_page_seo-and-social';
	$is_post_editor = $screen && in_array( $screen->post_type, array_unique( array_merge( sas_get_enabled_post_types( 'seo' ), sas_get_enabled_post_types( 'faq' ) ) ), true );
	$is_faq_editor = $screen && in_array( $screen->post_type, sas_get_enabled_post_types( 'faq' ), true );

	if ( ! $is_settings_page && ! $is_post_editor ) {
		return;
	}

	wp_enqueue_media();

	if ( $is_faq_editor ) {
		wp_enqueue_editor();
	}

	wp_enqueue_style(
		'sas-admin',
		SAS_PLUGIN_URL . 'assets/admin.css',
		array(),
		filemtime( SAS_PLUGIN_DIR . 'assets/admin.css' )
	);

	wp_enqueue_script(
		'sas-admin',
		SAS_PLUGIN_URL . 'assets/admin.js',
		array( 'jquery' ),
		filemtime( SAS_PLUGIN_DIR . 'assets/admin.js' ),
		true
	);

	wp_localize_script(
		'sas-admin',
		'sasAdmin',
		array(
			'chooseImage' => __( 'Choose image', 'seo-and-social' ),
			'useImage' => __( 'Use this image', 'seo-and-social' ),
			'confirmDeleteAllData' => __( 'This will permanently delete all Seo & Social settings, all SEO/FAQ post meta saved by this plugin, and all generated WebP OG images. Original media files will not be deleted. This cannot be undone. Continue?', 'seo-and-social' ),
			'confirmDeleteOgImages' => __( 'This will delete all generated WebP OG images created by Seo & Social. Settings, SEO/FAQ post meta, and original media files will not be deleted. Continue?', 'seo-and-social' ),
		)
	);
}
