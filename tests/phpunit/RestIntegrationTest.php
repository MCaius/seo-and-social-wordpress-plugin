<?php
/**
 * REST permission and contract integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Rest_Integration_Test extends Seo_And_Social_Test_Case {
	/**
	 * REST fields present before a test registers plugin fields.
	 *
	 * @var array
	 */
	private $additional_fields_before = array();

	/**
	 * Give every test a clean REST server and field registry snapshot.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		global $wp_rest_additional_fields, $wp_rest_server;
		$this->additional_fields_before = (array) $wp_rest_additional_fields;
		$wp_rest_server = new WP_REST_Server();
	}

	/**
	 * Restore global REST state after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_rest_additional_fields, $wp_rest_server;
		$wp_rest_additional_fields = $this->additional_fields_before;
		$wp_rest_server = null;
		parent::tear_down();
	}

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

	/**
	 * The public settings route returns only its documented public sections.
	 *
	 * @return void
	 */
	public function test_public_settings_route_returns_expected_payload() {
		$settings = sas_get_default_settings();
		$settings['social']['email'] = 'public@example.test';
		$settings['seo']['site_name'] = 'Public QA Site';
		$settings['llms']['site_summary'] = 'Private LLM draft';
		$this->set_plugin_settings( $settings );
		$this->initialize_rest_server();

		$request = new WP_REST_Request( 'GET', '/headless-seo/v1/site-settings' );
		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'social', 'seo' ), array_keys( $data ) );
		$this->assertSame( 'public@example.test', $data['social']['email'] );
		$this->assertSame( 'Public QA Site', $data['seo']['site_name'] );
		$this->assertArrayHasKey( 'default_og_image_original_url', $data['seo'] );
		$this->assertArrayHasKey( 'default_og_image_optimized', $data['seo'] );
		$this->assertArrayNotHasKey( 'settings', $data );
		$this->assertArrayNotHasKey( 'llms', $data );
	}

	/**
	 * A private route rejects anonymous dispatch and serves Administrators.
	 *
	 * @return void
	 */
	public function test_private_route_dispatch_requires_administrator() {
		$settings = sas_get_default_settings();
		$settings['settings']['enable_public_rest_endpoint'] = false;
		$this->set_plugin_settings( $settings );
		$this->initialize_rest_server();
		$request = new WP_REST_Request( 'GET', '/headless-seo/v1/site-settings' );

		$anonymous_response = rest_get_server()->dispatch( $request );
		$this->assertSame( rest_authorization_required_code(), $anonymous_response->get_status() );
		$this->assertSame( 'rest_forbidden', $anonymous_response->get_data()['code'] );

		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $administrator );
		$admin_response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $admin_response->get_status() );
		$this->assertArrayHasKey( 'social', $admin_response->get_data() );
		$this->assertArrayHasKey( 'seo', $admin_response->get_data() );
	}

	/**
	 * Custom SEO and FAQ field names appear on real content responses.
	 *
	 * @return void
	 */
	public function test_custom_content_fields_are_exposed_with_expected_shapes() {
		$settings = sas_get_default_settings();
		$settings['settings']['seo_rest_field_name'] = 'qa_seo';
		$settings['settings']['faq_rest_field_name'] = 'qa_faq';
		$this->set_plugin_settings( $settings );
		$post_id = self::factory()->post->create(
			array(
				'post_type' => 'post',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $post_id, SAS_SEO_META_KEY, array( 'seo_title' => 'REST title' ) );
		update_post_meta(
			$post_id,
			SAS_FAQ_META_KEY,
			array(
				array(
					'question' => 'REST question?',
					'answer' => 'REST answer',
					'enabled' => true,
					'position' => 1,
				),
			)
		);
		$this->initialize_rest_server();

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );
		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'qa_seo', $data );
		$this->assertArrayHasKey( 'seo_resolved', $data );
		$this->assertArrayHasKey( 'qa_faq', $data );
		$this->assertIsArray( $data['qa_seo'] );
		$this->assertSame( 'REST title', $data['qa_seo']['seo_title'] );
		$this->assertSame( 'REST question?', $data['qa_faq'][0]['question'] );
		$this->assertSame( 'override', $data['seo_resolved']['source']['seo_title'] );
	}

	/**
	 * Disabled content fields do not alter WordPress content responses.
	 *
	 * @return void
	 */
	public function test_disabled_content_fields_are_not_exposed() {
		$settings = sas_get_default_settings();
		$settings['settings']['enable_content_rest_fields'] = false;
		$this->set_plugin_settings( $settings );
		$post_id = self::factory()->post->create(
			array(
				'post_type' => 'post',
				'post_status' => 'publish',
			)
		);
		$this->initialize_rest_server();

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );
		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'seo_overrides', $data );
		$this->assertArrayNotHasKey( 'seo_resolved', $data );
		$this->assertArrayNotHasKey( 'faq_items', $data );
	}

	/**
	 * Register all REST routes and additional fields for the current settings.
	 *
	 * @return void
	 */
	private function initialize_rest_server() {
		do_action( 'rest_api_init', rest_get_server() );
	}
}
