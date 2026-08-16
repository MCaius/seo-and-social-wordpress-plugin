<?php
/**
 * Privileged admin-post security coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Admin_Post_Security_Test extends Seo_And_Social_Test_Case {
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
		$_POST[ $nonce_name ] = 'invalid-admin-post-nonce';

		$this->expectException( 'WPDieException' );
		$handler();
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
