<?php
/**
 * GitHub updater integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_GitHub_Updater_Integration_Test extends Seo_And_Social_Test_Case {
	/**
	 * Number of mocked HTTP requests.
	 *
	 * @var int
	 */
	private $request_count = 0;

	/**
	 * Mocked GitHub response body.
	 *
	 * @var string
	 */
	private $response_body = '';

	/**
	 * Mocked GitHub response code.
	 *
	 * @var int
	 */
	private $response_code = 200;

	/**
	 * Clean up HTTP mocks.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'mock_github_request' ), 10 );
		parent::tear_down();
	}

	/**
	 * The plugin declares its unique third-party update URI.
	 *
	 * @return void
	 */
	public function test_plugin_declares_github_update_uri() {
		$headers = get_file_data( SAS_PLUGIN_FILE, array( 'update_uri' => 'Update URI' ), 'plugin' );

		$this->assertSame( SAS_UPDATE_URI, $headers['update_uri'] );
	}

	/**
	 * A valid stable release is parsed from the allow-listed package asset.
	 *
	 * @return void
	 */
	public function test_valid_release_is_parsed() {
		$this->assertSame(
			array(
				'version' => '1.2.0',
				'url' => 'https://github.com/MCaius/seo-and-social-wordpress-plugin/releases/tag/v1.2.0',
				'package' => 'https://github.com/MCaius/seo-and-social-wordpress-plugin/releases/download/v1.2.0/seo-and-social.zip',
			),
			sas_parse_github_release( $this->valid_release() )
		);
	}

	/**
	 * Unsafe, incomplete, or unstable releases are rejected.
	 *
	 * @return void
	 */
	public function test_invalid_releases_are_rejected() {
		$draft = $this->valid_release();
		$draft['draft'] = true;
		$prerelease = $this->valid_release();
		$prerelease['prerelease'] = true;
		$invalid_tag = $this->valid_release();
		$invalid_tag['tag_name'] = 'latest';
		$missing_asset = $this->valid_release();
		$missing_asset['assets'] = array();
		$wrong_repository = $this->valid_release();
		$wrong_repository['assets'][0]['browser_download_url'] = 'https://github.com/example/other/releases/download/v1.2.0/seo-and-social.zip';

		$this->assertFalse( sas_parse_github_release( $draft ) );
		$this->assertFalse( sas_parse_github_release( $prerelease ) );
		$this->assertFalse( sas_parse_github_release( $invalid_tag ) );
		$this->assertFalse( sas_parse_github_release( $missing_asset ) );
		$this->assertFalse( sas_parse_github_release( $wrong_repository ) );
		$this->assertFalse( sas_parse_github_release( 'malformed' ) );
	}

	/**
	 * WordPress receives update metadata only for a newer plugin version.
	 *
	 * @return void
	 */
	public function test_newer_release_produces_wordpress_update_metadata() {
		set_site_transient( SAS_GITHUB_RELEASE_CACHE_KEY, array( 'release' => sas_parse_github_release( $this->valid_release() ) ), HOUR_IN_SECONDS );

		$update = sas_filter_github_plugin_update(
			false,
			array(
				'Version' => '1.1.0',
				'UpdateURI' => SAS_UPDATE_URI,
			),
			plugin_basename( SAS_PLUGIN_FILE ),
			array( 'en_US' )
		);

		$this->assertSame( '1.2.0', $update['version'] );
		$this->assertSame( 'https://github.com/MCaius/seo-and-social-wordpress-plugin/releases/download/v1.2.0/seo-and-social.zip', $update['package'] );
		$this->assertFalse( $update['autoupdate'] );

		$this->assertFalse(
			sas_filter_github_plugin_update(
				false,
				array(
					'Version' => '1.2.0',
					'UpdateURI' => SAS_UPDATE_URI,
				),
				plugin_basename( SAS_PLUGIN_FILE ),
				array( 'en_US' )
			)
		);
	}

	/**
	 * The GitHub hook does not alter update data for other plugins.
	 *
	 * @return void
	 */
	public function test_update_filter_ignores_other_plugins() {
		$existing = array( 'version' => '9.9.9' );

		$this->assertSame(
			$existing,
			sas_filter_github_plugin_update(
				$existing,
				array(
					'Version' => '1.0.0',
					'UpdateURI' => 'https://github.com/example/other-plugin',
				),
				'other-plugin/other-plugin.php',
				array( 'en_US' )
			)
		);
	}

	/**
	 * The GitHub response is cached and does not expose site information.
	 *
	 * @return void
	 */
	public function test_github_release_request_is_cached() {
		$this->response_body = wp_json_encode( $this->valid_release() );
		add_filter( 'pre_http_request', array( $this, 'mock_github_request' ), 10, 3 );

		$first = sas_get_latest_github_release();
		$second = sas_get_latest_github_release();

		$this->assertSame( '1.2.0', $first['version'] );
		$this->assertSame( $first, $second );
		$this->assertSame( 1, $this->request_count );
	}

	/**
	 * GitHub failures are cached briefly and fail without an update.
	 *
	 * @return void
	 */
	public function test_github_failure_fails_safely_and_is_cached() {
		$this->response_code = 503;
		$this->response_body = 'Service unavailable';
		add_filter( 'pre_http_request', array( $this, 'mock_github_request' ), 10, 3 );

		$this->assertFalse( sas_get_latest_github_release() );
		$this->assertFalse( sas_get_latest_github_release() );
		$this->assertSame( 1, $this->request_count );
	}

	/**
	 * A forced WordPress plugin check clears the GitHub response cache.
	 *
	 * @return void
	 */
	public function test_forced_plugin_check_clears_github_cache() {
		set_site_transient( SAS_GITHUB_RELEASE_CACHE_KEY, array( 'release' => array( 'version' => '1.2.0' ) ), HOUR_IN_SECONDS );

		sas_clear_github_release_cache();

		$this->assertFalse( get_site_transient( SAS_GITHUB_RELEASE_CACHE_KEY ) );
	}

	/**
	 * Mock the GitHub API response.
	 *
	 * @param false|array|WP_Error $preempt Preempted response.
	 * @param array                $args Request arguments.
	 * @param string               $url Request URL.
	 * @return array
	 */
	public function mock_github_request( $preempt, $args, $url ) {
		unset( $preempt );
		$this->request_count++;
		$this->assertSame( SAS_GITHUB_RELEASES_API, $url );
		$this->assertSame( 'Seo-and-Social-WordPress-Plugin/' . SAS_VERSION, $args['headers']['User-Agent'] );
		$this->assertStringNotContainsString( home_url(), wp_json_encode( $args ) );

		return array(
			'headers' => array(),
			'body' => $this->response_body,
			'response' => array(
				'code' => $this->response_code,
				'message' => $this->response_code === 200 ? 'OK' : 'Service unavailable',
			),
			'cookies' => array(),
			'filename' => null,
		);
	}

	/**
	 * Build a representative valid GitHub release payload.
	 *
	 * @return array
	 */
	private function valid_release() {
		return array(
			'tag_name' => 'v1.2.0',
			'html_url' => 'https://github.com/MCaius/seo-and-social-wordpress-plugin/releases/tag/v1.2.0',
			'draft' => false,
			'prerelease' => false,
			'assets' => array(
				array(
					'name' => 'seo-and-social.zip',
					'state' => 'uploaded',
					'browser_download_url' => 'https://github.com/MCaius/seo-and-social-wordpress-plugin/releases/download/v1.2.0/seo-and-social.zip',
				),
			),
		);
	}
}
