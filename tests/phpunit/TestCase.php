<?php
/**
 * Shared WordPress integration test case.
 *
 * @package SeoAndSocial
 */

abstract class Seo_And_Social_Test_Case extends WP_UnitTestCase {
	/**
	 * Reset plugin state before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		delete_option( SAS_OPTION_NAME );
		wp_set_current_user( 0 );
		$_POST = array();
		$_GET = array();
		$_REQUEST = array();
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP'] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
	}

	/**
	 * Remove test-only filters and state.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_role( 'qa_seo_manager' );
		remove_role( 'qa_seo_capability_manager' );
		remove_all_filters( 'sas_plugin_access_roles' );
		remove_all_filters( 'sas_plugin_access_capabilities' );
		remove_all_filters( 'sas_public_settings_rate_limit' );
		remove_all_filters( 'sas_public_settings_rate_limit_window' );
		remove_all_filters( 'sas_trusted_proxy_ip_headers' );
		delete_option( SAS_OPTION_NAME );
		$_POST = array();
		$_GET = array();
		$_REQUEST = array();

		parent::tear_down();
	}

	/**
	 * Store settings without autoloading them.
	 *
	 * @param array $settings Settings to store.
	 * @return void
	 */
	protected function set_plugin_settings( $settings ) {
		update_option( SAS_OPTION_NAME, $settings, false );
	}
}
