<?php
/**
 * REST permission and contract integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Rest_Integration_Test extends Seo_And_Social_Test_Case {
	/**
	 * Anonymous clients can access enabled public endpoints.
	 *
	 * @return void
	 */
	public function test_public_endpoint_permission_allows_anonymous_request() {
		$settings = sas_get_default_settings();
		$settings['settings']['enable_public_rest_endpoint'] = true;
		$this->set_plugin_settings( $settings );

		$this->assertTrue( sas_settings_endpoint_permission() );
	}

	/**
	 * Disabled public endpoints reject anonymous clients but allow Administrators.
	 *
	 * @return void
	 */
	public function test_private_endpoint_requires_administrator() {
		$settings = sas_get_default_settings();
		$settings['settings']['enable_public_rest_endpoint'] = false;
		$this->set_plugin_settings( $settings );

		$result = sas_settings_endpoint_permission();
		$this->assertWPError( $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );

		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $administrator );

		$this->assertTrue( sas_settings_endpoint_permission() );
	}

	/**
	 * The public rate limit returns 429 after the configured number of requests.
	 *
	 * @return void
	 */
	public function test_public_rate_limit_blocks_excess_request() {
		$settings = sas_get_default_settings();
		$settings['settings']['enable_public_rest_endpoint'] = true;
		$this->set_plugin_settings( $settings );
		$_SERVER['REMOTE_ADDR'] = '198.51.100.77';

		add_filter( 'sas_public_settings_rate_limit', static function () { return 2; } );
		add_filter( 'sas_public_settings_rate_limit_window', static function () { return 60; } );

		$this->assertTrue( sas_settings_endpoint_permission() );
		$this->assertTrue( sas_settings_endpoint_permission() );
		$result = sas_settings_endpoint_permission();

		$this->assertWPError( $result );
		$this->assertSame( 'sas_rate_limited', $result->get_error_code() );
		$this->assertSame( 429, $result->get_error_data()['status'] );
	}

	/**
	 * Configured REST routes are registered in a real WordPress REST server.
	 *
	 * @return void
	 */
	public function test_configured_routes_are_registered() {
		$settings = sas_get_default_settings();
		$settings['settings']['rest_namespace'] = 'qa-seo/v2';
		$settings['settings']['settings_endpoint_path'] = '/public-settings';
		$this->set_plugin_settings( $settings );

		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/qa-seo/v2/public-settings', $routes );
		$this->assertArrayHasKey( '/qa-seo/v2/llms', $routes );
	}
}
