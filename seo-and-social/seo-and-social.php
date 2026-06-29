<?php
/**
 * Plugin Name: Seo & Social
 * Description: Headless SEO, social, schema, and FAQ API settings for custom frontends.
 * Version: 0.1.0
 * Author: Caius
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: seo-and-social
 * Domain Path: /languages
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAS_PLUGIN_FILE', __FILE__ );
define( 'SAS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SAS_OPTION_NAME', 'sas_settings' );
define( 'SAS_TEXT_DOMAIN', 'seo-and-social' );
define( 'SAS_SEO_META_KEY', '_sas_seo_overrides' );
define( 'SAS_FAQ_META_KEY', '_sas_faq_items' );

$sas_files = array(
	'includes/helpers/settings.php',
	'includes/helpers/sanitization.php',
	'includes/helpers/og-image.php',
	'includes/helpers/assets.php',
	'includes/admin/admin-menu.php',
	'includes/meta-boxes/seo-meta-box.php',
	'includes/meta-boxes/faq-meta-box.php',
	'includes/rest/settings-endpoint.php',
	'includes/rest/content-fields.php',
);

foreach ( $sas_files as $sas_file ) {
	require_once SAS_PLUGIN_DIR . $sas_file;
}

add_action( 'plugins_loaded', 'sas_load_textdomain' );
add_action( 'admin_menu', 'sas_register_admin_menu' );
add_action( 'admin_enqueue_scripts', 'sas_enqueue_admin_assets' );
add_action( 'admin_post_sas_save_settings', 'sas_handle_save_settings' );
add_action( 'admin_post_sas_delete_all_data', 'sas_handle_delete_all_data' );
add_action( 'admin_post_sas_regenerate_og_images', 'sas_handle_regenerate_og_images' );
add_action( 'admin_post_sas_delete_optimized_og_images', 'sas_handle_delete_optimized_og_images' );
add_action( 'after_setup_theme', 'sas_register_og_image_size' );
add_action( 'delete_attachment', 'sas_delete_optimized_og_image' );
add_action( 'add_meta_boxes', 'sas_register_seo_meta_boxes' );
add_action( 'add_meta_boxes', 'sas_register_faq_meta_boxes' );
add_action( 'save_post', 'sas_save_seo_meta_box' );
add_action( 'save_post', 'sas_save_faq_meta_box' );
add_action( 'rest_api_init', 'sas_register_settings_endpoint' );
add_action( 'rest_api_init', 'sas_register_content_rest_fields' );
add_filter( 'rest_authentication_errors', 'sas_allow_public_settings_endpoint', 1000 );
add_filter( 'map_meta_cap', 'sas_map_plugin_access_capability', 10, 4 );

/**
 * Load plugin translations when language files are added later.
 */
function sas_load_textdomain() {
	load_plugin_textdomain( SAS_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Allow only administrators to access the global plugin admin page by default.
 *
 * @param array  $caps Required primitive capabilities.
 * @param string $cap Requested capability.
 * @param int    $user_id User ID.
 * @param array  $args Extra args.
 * @return array
 */
function sas_map_plugin_access_capability( $caps, $cap, $user_id, $args ) {
	if ( $cap !== 'sas_access_plugin' ) {
		return $caps;
	}

	$user = get_userdata( $user_id );

	if ( ! $user ) {
		return array( 'do_not_allow' );
	}

	$roles = (array) $user->roles;
	$allowed_roles = (array) apply_filters( 'sas_plugin_access_roles', array( 'administrator' ) );
	$allowed_capabilities = array_diff( (array) apply_filters( 'sas_plugin_access_capabilities', array() ), array( 'sas_access_plugin' ) );

	if ( array_intersect( $roles, $allowed_roles ) ) {
		return array( 'read' );
	}

	foreach ( $allowed_capabilities as $allowed_capability ) {
		if ( ! empty( $user->allcaps[ $allowed_capability ] ) ) {
			return array( 'read' );
		}
	}

	return array( 'do_not_allow' );
}
