<?php
/**
 * Settings defaults and accessors.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return default plugin settings.
 *
 * @return array
 */
function sas_get_default_settings() {
	return array(
		'social' => array(
			'email' => '',
			'phone_whatsapp' => '',
			'facebook_url' => '',
			'instagram_url' => '',
			'tiktok_url' => '',
			'youtube_url' => '',
			'extra_links' => array(),
		),
		'seo' => array(
			'site_name' => '',
			'default_meta_title' => '',
			'default_meta_description' => '',
			'default_robots' => '',
			'default_og_image_id' => 0,
			'default_og_image' => '',
			'schema_type' => 'Organization',
			'organization_name' => '',
			'logo_url' => '',
			'website_url' => '',
			'address' => '',
			'city' => '',
			'country' => '',
			'postal_code' => '',
			'latitude' => '',
			'longitude' => '',
			'opening_hours' => '',
			'price_range' => '',
			'extra_schema_properties' => array(),
			'custom_schema_json' => '',
		),
		'llms' => array(
			'enabled' => false,
			'site_summary' => '',
			'recommended_pages' => array(),
			'ignored_sections' => array(),
			'custom_content' => '',
		),
		'settings' => array(
			'enable_seo_meta_box' => true,
			'enable_faq_meta_box' => true,
			'seo_post_types' => sas_get_default_post_types(),
			'faq_post_types' => sas_get_default_post_types(),
			'faq_allow_html' => true,
			'enable_og_image_optimization' => false,
			'enable_public_rest_endpoint' => true,
			'enable_content_rest_fields' => true,
			'rest_namespace' => 'headless-seo/v1',
			'settings_endpoint_path' => '/site-settings',
			'seo_rest_field_name' => 'seo_overrides',
			'faq_rest_field_name' => 'faq_items',
		),
	);
}

/**
 * Get post types enabled by default.
 *
 * @return array
 */
function sas_get_default_post_types() {
	if ( ! function_exists( 'get_post_types' ) ) {
		return array( 'post', 'page' );
	}

	$post_types = get_post_types( array( 'public' => true ), 'names' );

	if ( ! is_array( $post_types ) || ! $post_types ) {
		return array( 'post', 'page' );
	}

	foreach ( array( 'post', 'page' ) as $required_post_type ) {
		if ( ! in_array( $required_post_type, $post_types, true ) ) {
			$post_types[] = $required_post_type;
		}
	}

	$post_types = array_diff( array_map( 'sanitize_key', $post_types ), array( 'attachment' ) );

	return array_values( array_unique( $post_types ) );
}

/**
 * Determine whether an array is a list.
 *
 * @param array $array Array to check.
 * @return bool
 */
function sas_is_list_array( $array ) {
	if ( $array === array() ) {
		return true;
	}

	return array_keys( $array ) === range( 0, count( $array ) - 1 );
}

/**
 * Recursively merge saved settings into defaults.
 *
 * @param array $defaults Defaults.
 * @param array $saved Saved values.
 * @return array
 */
function sas_merge_settings( $defaults, $saved ) {
	foreach ( $defaults as $key => $default_value ) {
		if ( ! array_key_exists( $key, $saved ) ) {
			continue;
		}

		if ( is_array( $default_value ) && is_array( $saved[ $key ] ) && ! sas_is_list_array( $default_value ) && ! sas_is_list_array( $saved[ $key ] ) ) {
			$defaults[ $key ] = sas_merge_settings( $default_value, $saved[ $key ] );
		} else {
			$defaults[ $key ] = $saved[ $key ];
		}
	}

	return $defaults;
}

/**
 * Get plugin settings.
 *
 * @return array
 */
function sas_get_settings() {
	$saved = get_option( SAS_OPTION_NAME, array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return sas_merge_settings( sas_get_default_settings(), $saved );
}

/**
 * Get public post types for settings checkboxes.
 *
 * @return array
 */
function sas_get_available_post_types() {
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	foreach ( array( 'post', 'page' ) as $required_post_type ) {
		if ( ! isset( $post_types[ $required_post_type ] ) ) {
			$object = get_post_type_object( $required_post_type );
			if ( $object ) {
				$post_types[ $required_post_type ] = $object;
			}
		}
	}

	uasort(
		$post_types,
		static function ( $a, $b ) {
			return strcasecmp( $a->labels->singular_name, $b->labels->singular_name );
		}
	);

	return $post_types;
}

/**
 * Get enabled post types for a feature.
 *
 * @param string $feature seo or faq.
 * @return array
 */
function sas_get_enabled_post_types( $feature ) {
	$settings = sas_get_settings();
	$key = $feature === 'faq' ? 'faq_post_types' : 'seo_post_types';
	$enabled = $settings['settings'][ $key ];
	$available = array_keys( sas_get_available_post_types() );

	if ( ! is_array( $enabled ) ) {
		return array();
	}

	return array_values( array_intersect( array_map( 'sanitize_key', $enabled ), $available ) );
}

/**
 * Sanitize a REST namespace.
 *
 * @param string $namespace Namespace.
 * @return string
 */
function sas_sanitize_rest_namespace( $namespace ) {
	$namespace = trim( (string) $namespace );
	$namespace = trim( $namespace, '/' );
	$namespace = preg_replace( '/[^a-zA-Z0-9_\-\/]/', '', $namespace );
	$namespace = preg_replace( '#/+#', '/', $namespace );

	return $namespace ? $namespace : 'headless-seo/v1';
}

/**
 * Sanitize a REST route path.
 *
 * @param string $path Route path.
 * @return string
 */
function sas_sanitize_rest_path( $path ) {
	$path = trim( (string) $path );
	$path = '/' . trim( $path, '/' );
	$path = preg_replace( '/[^a-zA-Z0-9_\-\/]/', '', $path );
	$path = preg_replace( '#/+#', '/', $path );

	return $path === '/' ? '/site-settings' : $path;
}

/**
 * Sanitize a REST field name.
 *
 * @param string $field Field name.
 * @param string $fallback Fallback field name.
 * @return string
 */
function sas_sanitize_rest_field_name( $field, $fallback ) {
	$field = sanitize_key( $field );

	if ( $field === 'seo_resolved' ) {
		return $fallback;
	}

	return $field ? $field : $fallback;
}
