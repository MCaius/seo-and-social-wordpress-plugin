<?php
/**
 * LLMs public-output integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Llms_Integration_Test extends Seo_And_Social_Test_Case {
	/**
	 * Disabled LLMs drafts are not exposed anonymously.
	 *
	 * @return void
	 */
	public function test_disabled_llms_draft_is_private_for_anonymous_user() {
		$settings = sas_get_default_settings();
		$settings['llms'] = array(
			'enabled' => false,
			'site_summary' => 'Private draft summary',
			'recommended_pages' => array(
				array(
					'label' => 'Private page',
					'url' => 'https://example.test/private',
					'note' => 'Draft note',
				),
			),
			'ignored_sections' => array(),
			'custom_content' => 'Private draft instructions',
		);
		$this->set_plugin_settings( $settings );

		$this->assertSame( array( 'enabled' => false ), sas_get_public_llms() );
	}

	/**
	 * Administrators can preview a disabled draft without making it public.
	 *
	 * @return void
	 */
	public function test_administrator_can_preview_disabled_llms_draft() {
		$settings = sas_get_default_settings();
		$settings['seo']['site_name'] = 'QA Site';
		$settings['llms']['enabled'] = false;
		$settings['llms']['site_summary'] = 'Private draft summary';
		$this->set_plugin_settings( $settings );

		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $administrator );
		$result = sas_get_public_llms();

		$this->assertFalse( $result['enabled'] );
		$this->assertSame( 'Private draft summary', $result['site_summary'] );
		$this->assertStringContainsString( '# QA Site', $result['rendered_txt'] );
		$this->assertStringContainsString( 'Private draft summary', $result['rendered_txt'] );
	}
}
