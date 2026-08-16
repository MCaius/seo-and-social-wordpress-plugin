<?php
/**
 * Settings integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Settings_Integration_Test extends Seo_And_Social_Test_Case {
	/**
	 * Older partial options receive new defaults without losing saved values.
	 *
	 * @return void
	 */
	public function test_partial_saved_option_is_merged_with_current_defaults() {
		$this->set_plugin_settings(
			array(
				'social' => array(
					'email' => 'legacy@example.test',
				),
				'settings' => array(
					'enable_public_rest_endpoint' => false,
				),
				'legacy_unknown_section' => array( 'value' => 'discarded' ),
			)
		);

		$settings = sas_get_settings();

		$this->assertSame( 'legacy@example.test', $settings['social']['email'] );
		$this->assertFalse( $settings['settings']['enable_public_rest_endpoint'] );
		$this->assertSame( 'Organization', $settings['seo']['schema_type'] );
		$this->assertTrue( $settings['settings']['enable_seo_meta_box'] );
		$this->assertArrayNotHasKey( 'legacy_unknown_section', $settings );
	}

	/**
	 * A malformed stored option safely falls back to current defaults.
	 *
	 * @return void
	 */
	public function test_non_array_saved_option_falls_back_to_defaults() {
		update_option( SAS_OPTION_NAME, 'malformed-option', false );

		$this->assertSame( sas_get_default_settings(), sas_get_settings() );
	}

	/**
	 * Saving Social must not erase other settings tabs.
	 *
	 * @return void
	 */
	public function test_social_save_preserves_other_tabs() {
		$existing = sas_get_default_settings();
		$existing['seo']['site_name'] = 'Existing site';
		$existing['llms']['site_summary'] = 'Existing summary';
		$existing['settings']['enable_public_rest_endpoint'] = false;

		$result = sas_sanitize_settings(
			array(
				'social' => array(
					'email' => 'new@example.com',
					'phone_whatsapp' => '+123456789',
					'facebook_url' => 'https://facebook.com/example',
					'extra_links' => array(),
				),
			),
			$existing,
			'social'
		);

		$this->assertSame( 'new@example.com', $result['social']['email'] );
		$this->assertSame( 'Existing site', $result['seo']['site_name'] );
		$this->assertSame( 'Existing summary', $result['llms']['site_summary'] );
		$this->assertFalse( $result['settings']['enable_public_rest_endpoint'] );
	}

	/**
	 * Custom REST names are normalized and reserved names fall back safely.
	 *
	 * @return void
	 */
	public function test_rest_names_are_sanitized() {
		$result = sas_sanitize_settings(
			array(
				'settings' => array(
					'enable_public_rest_endpoint' => '1',
					'enable_content_rest_fields' => '1',
					'rest_namespace' => ' /custom namespace//v2/ ',
					'settings_endpoint_path' => ' /site settings// ',
					'seo_rest_field_name' => 'seo_resolved',
					'faq_rest_field_name' => 'Custom FAQ',
				),
			),
			sas_get_default_settings(),
			'settings'
		);

		$this->assertSame( 'customnamespace/v2', $result['settings']['rest_namespace'] );
		$this->assertSame( '/sitesettings', $result['settings']['settings_endpoint_path'] );
		$this->assertSame( 'seo_overrides', $result['settings']['seo_rest_field_name'] );
		$this->assertSame( 'customfaq', $result['settings']['faq_rest_field_name'] );
	}

	/**
	 * Invalid schema JSON must not survive sanitization.
	 *
	 * @return void
	 */
	public function test_invalid_schema_json_is_removed() {
		$result = sas_sanitize_settings(
			array(
				'seo' => array(
					'site_name' => 'Example',
					'custom_schema_json' => '{invalid',
				),
			),
			sas_get_default_settings(),
			'seo'
		);

		$this->assertSame( '', $result['seo']['custom_schema_json'] );
	}

	/**
	 * Dynamic social rows retain only complete sanitized entries.
	 *
	 * @return void
	 */
	public function test_extra_social_links_are_structured_and_sanitized() {
		$result = sas_sanitize_settings(
			array(
				'social' => array(
					'extra_links' => array(
						array(
							'key' => ' QA Community ',
							'label' => '<b>Community</b>',
							'url' => 'https://example.test/community',
						),
						array(
							'key' => 'missing-url',
							'label' => 'Invalid row',
							'url' => '',
						),
					),
				),
			),
			sas_get_default_settings(),
			'social'
		);

		$this->assertCount( 1, $result['social']['extra_links'] );
		$this->assertSame( 'qa-community', $result['social']['extra_links'][0]['key'] );
		$this->assertSame( 'Community', $result['social']['extra_links'][0]['label'] );
		$this->assertSame( 'https://example.test/community', $result['social']['extra_links'][0]['url'] );
	}
}
