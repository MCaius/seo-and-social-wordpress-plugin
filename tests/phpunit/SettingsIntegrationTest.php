<?php
/**
 * Settings integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Settings_Integration_Test extends Seo_And_Social_Test_Case {
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
}
