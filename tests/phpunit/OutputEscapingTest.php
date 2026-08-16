<?php
/**
 * Stored XSS and output escaping coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Output_Escaping_Test extends Seo_And_Social_Test_Case {
	/**
	 * Admin notices sanitize query input and escape the rendered message.
	 *
	 * @return void
	 */
	public function test_admin_notice_query_is_not_rendered_as_html() {
		$this->set_administrator();
		$this->set_plugin_settings( sas_get_default_settings() );
		$_GET['sas_notice'] = '<img src=x onerror=alert(1)>Maintenance & complete';
		$_GET['sas_status'] = 'error" onmouseover="alert(2)';

		$output = $this->capture_output( 'sas_render_admin_page' );

		$this->assertStringNotContainsString( '<img src=x', $output );
		$this->assertStringNotContainsString( 'onmouseover="alert(2)"', $output );
		$this->assertStringContainsString( 'Maintenance &amp; complete', $output );
		$this->assertStringContainsString( 'notice-success', $output );
	}

	/**
	 * Raw stored settings and schema rows remain escaped in Admin HTML.
	 *
	 * @return void
	 */
	public function test_stored_settings_and_schema_values_are_escaped_in_admin() {
		$this->set_administrator();
		$settings = sas_get_default_settings();
		$settings['seo']['site_name'] = '"><script>alert(1)</script>';
		$settings['seo']['custom_schema_json'] = '</textarea><script>alert(2)</script>';
		$settings['seo']['extra_schema_properties'] = array(
			array(
				'key' => '"><img src=x onerror=alert(3)>',
				'type' => 'text',
				'value' => '</textarea><script>alert(4)</script>',
			),
		);
		$this->set_plugin_settings( $settings );
		$_GET['tab'] = 'seo';

		$output = $this->capture_output( 'sas_render_admin_page' );

		$this->assertStringNotContainsString( '"><script>alert(1)</script>', $output );
		$this->assertStringNotContainsString( '</textarea><script>alert(2)</script>', $output );
		$this->assertStringNotContainsString( '"><img src=x onerror=alert(3)>', $output );
		$this->assertStringNotContainsString( '</textarea><script>alert(4)</script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $output );
		$this->assertStringContainsString( '&lt;/textarea&gt;&lt;script&gt;alert(4)&lt;/script&gt;', $output );
	}

	/**
	 * Raw SEO post metadata cannot break out of Admin form controls.
	 *
	 * @return void
	 */
	public function test_stored_seo_metadata_is_escaped_in_meta_box() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		update_post_meta(
			$post_id,
			SAS_SEO_META_KEY,
			array(
				'seo_title' => '"><script>alert(5)</script>',
				'seo_description' => '</textarea><script>alert(6)</script>',
				'custom_schema_json' => '</textarea><img src=x onerror=alert(7)>',
			)
		);

		$output = $this->capture_output(
			static function () use ( $post_id ) {
				sas_render_seo_meta_box( get_post( $post_id ) );
			}
		);

		$this->assertStringNotContainsString( '"><script>alert(5)</script>', $output );
		$this->assertStringNotContainsString( '</textarea><script>alert(6)</script>', $output );
		$this->assertStringNotContainsString( '</textarea><img src=x onerror=alert(7)>', $output );
		$this->assertStringContainsString( '&lt;script&gt;alert(5)&lt;/script&gt;', $output );
		$this->assertStringContainsString( '&lt;/textarea&gt;&lt;script&gt;alert(6)&lt;/script&gt;', $output );
	}

	/**
	 * Raw FAQ post metadata is escaped in row labels, inputs, and textareas.
	 *
	 * @return void
	 */
	public function test_stored_faq_metadata_is_escaped_in_meta_box() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		update_post_meta(
			$post_id,
			SAS_FAQ_META_KEY,
			array(
				array(
					'question' => '"><img src=x onerror=alert(8)>',
					'answer' => '</textarea><script>alert(9)</script>',
					'enabled' => true,
					'position' => '1" autofocus onfocus="alert(10)',
				),
			)
		);

		$output = $this->capture_output(
			static function () use ( $post_id ) {
				sas_render_faq_meta_box( get_post( $post_id ) );
			}
		);

		$this->assertStringNotContainsString( '"><img src=x onerror=alert(8)>', $output );
		$this->assertStringNotContainsString( '</textarea><script>alert(9)</script>', $output );
		$this->assertStringNotContainsString( 'value="1" autofocus onfocus="alert(10)"', $output );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(8)&gt;', $output );
		$this->assertStringContainsString( '&lt;/textarea&gt;&lt;script&gt;alert(9)&lt;/script&gt;', $output );
	}

	/**
	 * Set the current user to an Administrator.
	 *
	 * @return void
	 */
	private function set_administrator() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
	}

	/**
	 * Capture output from a callable.
	 *
	 * @param callable $callback Render callback.
	 * @return string
	 */
	private function capture_output( $callback ) {
		ob_start();
		call_user_func( $callback );
		return (string) ob_get_clean();
	}
}
