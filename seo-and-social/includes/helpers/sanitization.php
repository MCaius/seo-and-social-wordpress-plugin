<?php
/**
 * Sanitization and public data helpers.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a multiline text value.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function sas_sanitize_textarea( $value ) {
	return sanitize_textarea_field( (string) $value );
}

/**
 * Validate and normalize JSON.
 *
 * @param string $json Raw JSON.
 * @return string
 */
function sas_sanitize_json_string( $json ) {
	$json = trim( (string) $json );

	if ( $json === '' ) {
		return '';
	}

	$decoded = json_decode( $json, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return '';
	}

	return wp_json_encode( $decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/**
 * Sanitize a schema property key without breaking Schema.org camelCase names.
 *
 * @param mixed $key Raw key.
 * @return string
 */
function sas_sanitize_schema_property_key( $key ) {
	$key = trim( (string) $key );
	$key = preg_replace( '/[^A-Za-z0-9_\-:@.]/', '', $key );

	return $key ? $key : '';
}

/**
 * Decode stored JSON for public output.
 *
 * @param string $json Stored JSON.
 * @return mixed
 */
function sas_decode_json_for_public( $json ) {
	$json = trim( (string) $json );

	if ( $json === '' ) {
		return '';
	}

	$decoded = json_decode( $json, true );

	return json_last_error() === JSON_ERROR_NONE ? $decoded : '';
}

/**
 * Sanitize extra social link rows.
 *
 * @param mixed $rows Raw rows.
 * @return array
 */
function sas_sanitize_extra_social_links( $rows ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$clean = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$key = isset( $row['key'] ) ? sanitize_title( $row['key'] ) : '';
		$label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
		$url = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';

		if ( ! $key || ! $label || ! $url ) {
			continue;
		}

		$clean[] = array(
			'key' => $key,
			'label' => $label,
			'url' => $url,
		);
	}

	return $clean;
}

/**
 * Sanitize extra schema property rows.
 *
 * @param mixed $rows Raw rows.
 * @return array
 */
function sas_sanitize_extra_schema_properties( $rows ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$clean = array();
	$allowed_types = array( 'text', 'url', 'list', 'json' );

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$key = isset( $row['key'] ) ? sas_sanitize_schema_property_key( $row['key'] ) : '';
		$type = isset( $row['type'] ) ? sanitize_key( $row['type'] ) : 'text';
		$raw_value = $row['value'] ?? '';

		if ( ! $key || ! in_array( $type, $allowed_types, true ) ) {
			continue;
		}

		if ( $type === 'url' ) {
			$value = esc_url_raw( $raw_value );
		} elseif ( $type === 'list' ) {
			$lines = preg_split( '/\r\n|\r|\n/', (string) $raw_value );
			$value = array_values(
				array_filter(
					array_map( 'sanitize_text_field', $lines ),
					static function ( $item ) {
						return $item !== '';
					}
				)
			);
		} elseif ( $type === 'json' ) {
			$value = sas_sanitize_json_string( $raw_value );
		} else {
			$value = sanitize_text_field( $raw_value );
		}

		if ( $value === '' || $value === array() ) {
			continue;
		}

		$clean[] = array(
			'key' => $key,
			'type' => $type,
			'value' => $value,
		);
	}

	return $clean;
}

/**
 * Convert schema rows to public shape.
 *
 * @param array $rows Stored rows.
 * @return array
 */
function sas_prepare_public_schema_properties( $rows ) {
	$public = array();

	foreach ( (array) $rows as $row ) {
		if ( ! is_array( $row ) || empty( $row['key'] ) || empty( $row['type'] ) ) {
			continue;
		}

		$value = $row['value'] ?? '';

		if ( $row['type'] === 'json' ) {
			$value = sas_decode_json_for_public( $value );
		}

		if ( $value === '' || $value === array() ) {
			continue;
		}

		$public[] = array(
			'key' => sas_sanitize_schema_property_key( $row['key'] ),
			'type' => sanitize_key( $row['type'] ),
			'value' => $value,
		);
	}

	return $public;
}

/**
 * Sanitize recommended LLMs.txt page rows.
 *
 * @param mixed $rows Raw rows.
 * @return array
 */
function sas_sanitize_llms_recommended_pages( $rows ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$clean = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
		$url = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';
		$note = isset( $row['note'] ) ? sas_sanitize_textarea( $row['note'] ) : '';

		if ( ! $label || ! $url ) {
			continue;
		}

		$clean[] = array(
			'label' => $label,
			'url' => $url,
			'note' => $note,
		);
	}

	return $clean;
}

/**
 * Sanitize ignored LLMs.txt section rows.
 *
 * @param mixed $rows Raw rows.
 * @return array
 */
function sas_sanitize_llms_ignored_sections( $rows ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$clean = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
		$note = isset( $row['note'] ) ? sas_sanitize_textarea( $row['note'] ) : '';

		if ( ! $label && ! $note ) {
			continue;
		}

		$clean[] = array(
			'label' => $label,
			'note' => $note,
		);
	}

	return $clean;
}

/**
 * Sanitize full settings payload.
 *
 * @param array       $raw Raw POST settings.
 * @param array|null  $existing Existing settings.
 * @param string|null $active_tab Active tab being saved.
 * @return array
 */
function sas_sanitize_settings( $raw, $existing = null, $active_tab = null ) {
	$raw = is_array( $raw ) ? $raw : array();
	$settings = is_array( $existing ) ? sas_merge_settings( sas_get_default_settings(), $existing ) : sas_get_default_settings();
	$active_tab = $active_tab ? sanitize_key( $active_tab ) : '';
	$available_post_types = array_keys( sas_get_available_post_types() );

	if ( ( $active_tab === '' || $active_tab === 'social' ) && array_key_exists( 'social', $raw ) && is_array( $raw['social'] ) ) {
		$social = $raw['social'];
		$settings['social'] = array(
			'email' => isset( $social['email'] ) ? sanitize_email( $social['email'] ) : '',
			'phone_whatsapp' => isset( $social['phone_whatsapp'] ) ? sanitize_text_field( $social['phone_whatsapp'] ) : '',
			'facebook_url' => isset( $social['facebook_url'] ) ? esc_url_raw( $social['facebook_url'] ) : '',
			'instagram_url' => isset( $social['instagram_url'] ) ? esc_url_raw( $social['instagram_url'] ) : '',
			'tiktok_url' => isset( $social['tiktok_url'] ) ? esc_url_raw( $social['tiktok_url'] ) : '',
			'youtube_url' => isset( $social['youtube_url'] ) ? esc_url_raw( $social['youtube_url'] ) : '',
			'extra_links' => sas_sanitize_extra_social_links( $social['extra_links'] ?? array() ),
		);
	}

	if ( ( $active_tab === '' || $active_tab === 'seo' ) && array_key_exists( 'seo', $raw ) && is_array( $raw['seo'] ) ) {
		$seo = $raw['seo'];
		$default_robots = isset( $seo['default_robots'] ) ? sanitize_text_field( $seo['default_robots'] ) : '';

		if ( ! in_array( $default_robots, array_keys( sas_get_robots_options() ), true ) ) {
			$default_robots = '';
		}

		$settings['seo'] = array(
			'site_name' => isset( $seo['site_name'] ) ? sanitize_text_field( $seo['site_name'] ) : '',
			'default_meta_title' => isset( $seo['default_meta_title'] ) ? sanitize_text_field( $seo['default_meta_title'] ) : '',
			'default_meta_description' => isset( $seo['default_meta_description'] ) ? sas_sanitize_textarea( $seo['default_meta_description'] ) : '',
			'default_robots' => $default_robots,
			'default_og_image_id' => isset( $seo['default_og_image_id'] ) ? absint( $seo['default_og_image_id'] ) : 0,
			'default_og_image' => isset( $seo['default_og_image'] ) ? esc_url_raw( $seo['default_og_image'] ) : '',
			'schema_type' => isset( $seo['schema_type'] ) ? sanitize_text_field( $seo['schema_type'] ) : 'Organization',
			'organization_name' => isset( $seo['organization_name'] ) ? sanitize_text_field( $seo['organization_name'] ) : '',
			'logo_url' => isset( $seo['logo_url'] ) ? esc_url_raw( $seo['logo_url'] ) : '',
			'website_url' => isset( $seo['website_url'] ) ? esc_url_raw( $seo['website_url'] ) : '',
			'address' => isset( $seo['address'] ) ? sanitize_text_field( $seo['address'] ) : '',
			'city' => isset( $seo['city'] ) ? sanitize_text_field( $seo['city'] ) : '',
			'country' => isset( $seo['country'] ) ? sanitize_text_field( $seo['country'] ) : '',
			'postal_code' => isset( $seo['postal_code'] ) ? sanitize_text_field( $seo['postal_code'] ) : '',
			'latitude' => isset( $seo['latitude'] ) ? sanitize_text_field( $seo['latitude'] ) : '',
			'longitude' => isset( $seo['longitude'] ) ? sanitize_text_field( $seo['longitude'] ) : '',
			'opening_hours' => isset( $seo['opening_hours'] ) ? sanitize_text_field( $seo['opening_hours'] ) : '',
			'price_range' => isset( $seo['price_range'] ) ? sanitize_text_field( $seo['price_range'] ) : '',
			'extra_schema_properties' => sas_sanitize_extra_schema_properties( $seo['extra_schema_properties'] ?? array() ),
			'custom_schema_json' => isset( $seo['custom_schema_json'] ) ? sas_sanitize_json_string( $seo['custom_schema_json'] ) : '',
		);
	}

	if ( ( $active_tab === '' || $active_tab === 'llms' ) && array_key_exists( 'llms', $raw ) && is_array( $raw['llms'] ) ) {
		$llms = $raw['llms'];
		$settings['llms'] = array(
			'enabled' => ! empty( $llms['enabled'] ),
			'site_summary' => isset( $llms['site_summary'] ) ? sas_sanitize_textarea( $llms['site_summary'] ) : '',
			'recommended_pages' => sas_sanitize_llms_recommended_pages( $llms['recommended_pages'] ?? array() ),
			'ignored_sections' => sas_sanitize_llms_ignored_sections( $llms['ignored_sections'] ?? array() ),
			'custom_content' => isset( $llms['custom_content'] ) ? sas_sanitize_textarea( $llms['custom_content'] ) : '',
		);
	}

	if ( ( $active_tab === '' || $active_tab === 'settings' ) && array_key_exists( 'settings', $raw ) && is_array( $raw['settings'] ) ) {
		$behavior = $raw['settings'];
		$settings['settings'] = array(
			'enable_seo_meta_box' => ! empty( $behavior['enable_seo_meta_box'] ),
			'enable_faq_meta_box' => ! empty( $behavior['enable_faq_meta_box'] ),
			'seo_post_types' => array_values( array_intersect( array_map( 'sanitize_key', (array) ( $behavior['seo_post_types'] ?? array() ) ), $available_post_types ) ),
			'faq_post_types' => array_values( array_intersect( array_map( 'sanitize_key', (array) ( $behavior['faq_post_types'] ?? array() ) ), $available_post_types ) ),
			'faq_allow_html' => ! empty( $behavior['faq_allow_html'] ),
			'enable_og_image_optimization' => ! empty( $behavior['enable_og_image_optimization'] ),
			'enable_public_rest_endpoint' => ! empty( $behavior['enable_public_rest_endpoint'] ),
			'enable_content_rest_fields' => ! empty( $behavior['enable_content_rest_fields'] ),
			'rest_namespace' => sas_sanitize_rest_namespace( $behavior['rest_namespace'] ?? '' ),
			'settings_endpoint_path' => sas_sanitize_rest_path( $behavior['settings_endpoint_path'] ?? '' ),
			'seo_rest_field_name' => sas_sanitize_rest_field_name( $behavior['seo_rest_field_name'] ?? '', 'seo_overrides' ),
			'faq_rest_field_name' => sas_sanitize_rest_field_name( $behavior['faq_rest_field_name'] ?? '', 'faq_items' ),
		);
	}

	return $settings;
}

/**
 * Prepare global settings for the public REST endpoint.
 *
 * @return array
 */
function sas_get_public_settings() {
	$settings = sas_get_settings();
	$seo = $settings['seo'];

	$seo['extra_schema_properties'] = sas_prepare_public_schema_properties( $seo['extra_schema_properties'] );
	$seo['default_og_image_original_url'] = $seo['default_og_image'];
	$seo['default_og_image_optimized'] = null;

	if ( ! empty( $seo['default_og_image_id'] ) ) {
		$attachment_url = wp_get_attachment_image_url( (int) $seo['default_og_image_id'], 'full' );
		$original_url = $attachment_url ? $attachment_url : $seo['default_og_image'];
		$optimized = sas_get_optimized_og_image( (int) $seo['default_og_image_id'] );

		$seo['default_og_image_original_url'] = $original_url;
		$seo['default_og_image'] = $original_url;

		if ( $optimized ) {
			$seo['default_og_image_optimized'] = array(
				'url' => $optimized['url'],
				'width' => (int) $optimized['width'],
				'height' => (int) $optimized['height'],
				'mime' => $optimized['mime'],
			);
			$seo['default_og_image'] = $optimized['url'];
		}
	}

	return array(
		'social' => $settings['social'],
		'seo' => $seo,
	);
}

/**
 * Render the final LLMs.txt text from structured settings.
 *
 * @param array|null $llms LLM settings.
 * @return string
 */
function sas_render_llms_txt( $llms = null ) {
	$settings = sas_get_settings();
	$llms = is_array( $llms ) ? sas_merge_settings( sas_get_default_settings()['llms'], $llms ) : $settings['llms'];
	$seo = $settings['seo'];
	$title = $seo['site_name'] ? $seo['site_name'] : get_bloginfo( 'name' );
	$lines = array();

	if ( $title ) {
		$lines[] = '# ' . $title;
		$lines[] = '';
	}

	if ( ! empty( $llms['site_summary'] ) ) {
		$lines[] = '## Site summary';
		$lines[] = trim( $llms['site_summary'] );
		$lines[] = '';
	}

	if ( ! empty( $llms['recommended_pages'] ) ) {
		$lines[] = '## Recommended pages';

		foreach ( $llms['recommended_pages'] as $page ) {
			$line = '- [' . $page['label'] . '](' . $page['url'] . ')';

			if ( ! empty( $page['note'] ) ) {
				$line .= ': ' . $page['note'];
			}

			$lines[] = $line;
		}

		$lines[] = '';
	}

	if ( ! empty( $llms['ignored_sections'] ) ) {
		$lines[] = '## Ignored sections';

		foreach ( $llms['ignored_sections'] as $section ) {
			$label = ! empty( $section['label'] ) ? $section['label'] : $section['note'];
			$line = '- ' . $label;

			if ( ! empty( $section['label'] ) && ! empty( $section['note'] ) ) {
				$line .= ': ' . $section['note'];
			}

			$lines[] = $line;
		}

		$lines[] = '';
	}

	if ( ! empty( $llms['custom_content'] ) ) {
		$lines[] = '## Additional instructions';
		$lines[] = trim( $llms['custom_content'] );
		$lines[] = '';
	}

	return trim( implode( "\n", $lines ) );
}

/**
 * Prepare LLMs.txt payload for the public REST endpoint.
 *
 * @return array
 */
function sas_get_public_llms() {
	$settings = sas_get_settings();
	$llms = $settings['llms'];

	if ( empty( $llms['enabled'] ) && ! current_user_can( 'manage_options' ) ) {
		return array(
			'enabled' => false,
		);
	}

	return array(
		'enabled' => (bool) $llms['enabled'],
		'site_summary' => $llms['site_summary'],
		'recommended_pages' => $llms['recommended_pages'],
		'ignored_sections' => $llms['ignored_sections'],
		'custom_content' => $llms['custom_content'],
		'rendered_txt' => sas_render_llms_txt( $llms ),
	);
}
