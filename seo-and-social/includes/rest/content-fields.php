<?php
/**
 * Per-content REST field registration.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register configured REST fields.
 *
 * @return void
 */
function sas_register_content_rest_fields() {
	$settings = sas_get_settings();

	if ( empty( $settings['settings']['enable_content_rest_fields'] ) ) {
		return;
	}

	$seo_field = $settings['settings']['seo_rest_field_name'];
	$faq_field = $settings['settings']['faq_rest_field_name'];

	if ( ! empty( $settings['settings']['enable_seo_meta_box'] ) ) {
		foreach ( sas_get_enabled_post_types( 'seo' ) as $post_type ) {
			register_rest_field(
				$post_type,
				$seo_field,
				array(
					'get_callback' => static function ( $post ) {
						return sas_get_public_post_seo_overrides( (int) $post['id'] );
					},
					'schema' => array(
						'description' => 'SEO overrides for this content item.',
						'type' => 'object',
						'context' => array( 'view', 'edit' ),
					),
				)
			);

			if ( $seo_field !== 'seo_resolved' ) {
				register_rest_field(
					$post_type,
					'seo_resolved',
					array(
						'get_callback' => static function ( $post ) {
							return sas_get_resolved_post_seo( (int) $post['id'] );
						},
						'schema' => array(
							'description' => 'Resolved SEO data for this content item, using local overrides first and global Seo & Social defaults as fallback.',
							'type' => 'object',
							'context' => array( 'view', 'edit' ),
						),
					)
				);
			}
		}
	}

	if ( ! empty( $settings['settings']['enable_faq_meta_box'] ) ) {
		foreach ( sas_get_enabled_post_types( 'faq' ) as $post_type ) {
			register_rest_field(
				$post_type,
				$faq_field,
				array(
					'get_callback' => static function ( $post ) {
						return sas_get_public_faq_items( (int) $post['id'] );
					},
					'schema' => array(
						'description' => 'FAQ items for this content item.',
						'type' => 'array',
						'context' => array( 'view', 'edit' ),
					),
				)
			);
		}
	}
}
