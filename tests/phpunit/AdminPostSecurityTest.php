<?php
/**
 * Privileged admin-post security coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Admin_Post_Security_Test extends Seo_And_Social_Test_Case {
	/**
	 * Last redirect captured during a handler call.
	 *
	 * @var string
	 */
	private $redirect_location = '';

	/**
	 * Capture redirects without sending CLI headers.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->redirect_location = '';
		add_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );
	}

	/**
	 * Remove the redirect capture filter.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );
		parent::tear_down();
	}

	/**
	 * Store a redirect and stop WordPress from sending its header in PHPUnit.
	 *
	 * @param string $location Redirect location.
	 * @return false
	 */
	public function capture_redirect( $location ) {
		$this->redirect_location = $location;
		return false;
	}

	/**
	 * Every privileged handler is registered on its expected admin-post action.
	 *
	 * @dataProvider handler_provider
	 *
	 * @param string $action     Admin-post action.
	 * @param string $handler    Handler function.
	 * @param string $nonce_name POST nonce field.
	 * @return void
	 */
	public function test_privileged_handler_is_registered( $action, $handler, $nonce_name ) {
		$this->assertSame( 10, has_action( 'admin_post_' . $action, $handler ) );
		$this->assertNotSame( '', $nonce_name );
	}

	/**
	 * A Subscriber is stopped by the capability check before nonce processing.
	 *
	 * @dataProvider handler_provider
	 *
	 * @param string $action  Admin-post action.
	 * @param string $handler Handler function.
	 * @param string $nonce_name POST nonce field.
	 * @return void
	 */
	public function test_subscriber_cannot_execute_privileged_handler( $action, $handler, $nonce_name ) {
		$this->assertSame( 10, has_action( 'admin_post_' . $action, $handler ) );
		$this->assertNotSame( '', $nonce_name );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$this->expectException( 'WPDieException' );
		$handler();
	}

	/**
	 * An Administrator is stopped when the handler nonce is missing.
	 *
	 * @dataProvider handler_provider
	 *
	 * @param string $action     Admin-post action.
	 * @param string $handler    Handler function.
	 * @param string $nonce_name POST nonce field.
	 * @return void
	 */
	public function test_administrator_cannot_execute_handler_without_nonce( $action, $handler, $nonce_name ) {
		$this->assertSame( 10, has_action( 'admin_post_' . $action, $handler ) );
		$this->assertArrayNotHasKey( $nonce_name, $_REQUEST );
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $administrator );

		$this->expectException( 'WPDieException' );
		$handler();
	}

	/**
	 * An Administrator is stopped when the handler nonce is invalid.
	 *
	 * @dataProvider handler_provider
	 *
	 * @param string $action     Admin-post action.
	 * @param string $handler    Handler function.
	 * @param string $nonce_name POST nonce field.
	 * @return void
	 */
	public function test_administrator_cannot_execute_handler_with_invalid_nonce( $action, $handler, $nonce_name ) {
		$this->assertSame( 10, has_action( 'admin_post_' . $action, $handler ) );
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $administrator );
		$this->setRequestNonce( $nonce_name, 'invalid-admin-post-nonce' );

		$this->expectException( 'WPDieException' );
		$handler();
	}

	/**
	 * A valid settings request saves data and redirects back to its active tab.
	 *
	 * @return void
	 */
	public function test_administrator_can_save_settings_with_valid_nonce() {
		$this->set_administrator();
		$this->setRequestNonce( 'sas_settings_nonce', wp_create_nonce( 'sas_save_settings' ) );
		$_POST['sas_active_tab'] = 'social';
		$_POST['sas_settings'] = array(
			'social' => array(
				'email' => 'qa@example.test',
			),
		);

		sas_handle_save_settings();

		$settings = get_option( SAS_OPTION_NAME );
		$this->assertSame( 'qa@example.test', $settings['social']['email'] );
		$this->assertRedirectQueryContains(
			array(
				'tab' => 'social',
				'sas_message' => 'saved',
			)
		);
	}

	/**
	 * A valid delete-data request removes settings and plugin post metadata.
	 *
	 * @return void
	 */
	public function test_administrator_can_delete_all_data_with_valid_nonce() {
		$this->set_administrator();
		$post_id = self::factory()->post->create();
		$this->set_plugin_settings( sas_get_default_settings() );
		update_post_meta( $post_id, SAS_SEO_META_KEY, array( 'seo_title' => 'Delete me' ) );
		update_post_meta( $post_id, SAS_FAQ_META_KEY, array( array( 'question' => 'Delete me?' ) ) );
		$this->setRequestNonce( 'sas_delete_all_data_nonce', wp_create_nonce( 'sas_delete_all_data' ) );

		sas_handle_delete_all_data();

		$this->assertFalse( get_option( SAS_OPTION_NAME, false ) );
		$this->assertSame( '', get_post_meta( $post_id, SAS_SEO_META_KEY, true ) );
		$this->assertSame( '', get_post_meta( $post_id, SAS_FAQ_META_KEY, true ) );
		$this->assertRedirectQueryContains(
			array(
				'tab' => 'settings',
				'sas_message' => 'deleted',
			)
		);
	}

	/**
	 * A valid regeneration request completes and reports its result.
	 *
	 * @return void
	 */
	public function test_administrator_can_regenerate_og_images_with_valid_nonce() {
		$this->set_administrator();
		$this->setRequestNonce( 'sas_regenerate_og_images_nonce', wp_create_nonce( 'sas_regenerate_og_images' ) );

		sas_handle_regenerate_og_images();

		$this->assertRedirectQueryContains(
			array(
				'tab' => 'settings',
				'sas_status' => 'success',
			)
		);
		$this->assertRedirectQueryValueContains( 'sas_notice', 'Generated: 0' );
	}

	/**
	 * A valid cleanup request completes and reports its result.
	 *
	 * @return void
	 */
	public function test_administrator_can_delete_optimized_og_images_with_valid_nonce() {
		$this->set_administrator();
		$this->setRequestNonce( 'sas_delete_optimized_og_images_nonce', wp_create_nonce( 'sas_delete_optimized_og_images' ) );

		sas_handle_delete_optimized_og_images();

		$this->assertRedirectQueryContains(
			array(
				'tab' => 'settings',
				'sas_status' => 'success',
			)
		);
		$this->assertRedirectQueryValueContains( 'sas_notice', 'deleted: 0' );
	}

	/**
	 * Set the current user to an Administrator.
	 *
	 * @return int User ID.
	 */
	private function set_administrator() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		return $user_id;
	}

	/**
	 * Populate the nonce as PHP would for a real form request.
	 *
	 * @param string $name  Nonce field name.
	 * @param string $value Nonce value.
	 * @return void
	 */
	private function setRequestNonce( $name, $value ) {
		$_POST[ $name ] = $value;
		$_REQUEST[ $name ] = $value;
	}

	/**
	 * Assert values from the captured redirect query.
	 *
	 * @param array $expected Expected query values.
	 * @return void
	 */
	private function assertRedirectQueryContains( $expected ) {
		$actual = $this->getRedirectQuery();

		foreach ( $expected as $key => $value ) {
			$this->assertArrayHasKey( $key, $actual );
			$this->assertSame( $value, $actual[ $key ] );
		}
	}

	/**
	 * Assert that one captured redirect value contains a string.
	 *
	 * @param string $key    Query key.
	 * @param string $needle Expected partial value.
	 * @return void
	 */
	private function assertRedirectQueryValueContains( $key, $needle ) {
		$query = $this->getRedirectQuery();
		$this->assertArrayHasKey( $key, $query );
		$this->assertStringContainsString( $needle, $query[ $key ] );
	}

	/**
	 * Parse the captured redirect query.
	 *
	 * @return array
	 */
	private function getRedirectQuery() {
		$query_string = wp_parse_url( $this->redirect_location, PHP_URL_QUERY );
		$query = array();
		parse_str( (string) $query_string, $query );
		return $query;
	}

	/**
	 * Admin-post handler definitions.
	 *
	 * @return array
	 */
	public function handler_provider() {
		return array(
			'save settings' => array( 'sas_save_settings', 'sas_handle_save_settings', 'sas_settings_nonce' ),
			'delete all data' => array( 'sas_delete_all_data', 'sas_handle_delete_all_data', 'sas_delete_all_data_nonce' ),
			'regenerate OG images' => array( 'sas_regenerate_og_images', 'sas_handle_regenerate_og_images', 'sas_regenerate_og_images_nonce' ),
			'delete OG images' => array( 'sas_delete_optimized_og_images', 'sas_handle_delete_optimized_og_images', 'sas_delete_optimized_og_images_nonce' ),
		);
	}
}
