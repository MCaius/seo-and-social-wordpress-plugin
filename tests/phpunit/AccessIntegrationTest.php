<?php
/**
 * Role and capability integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Access_Integration_Test extends Seo_And_Social_Test_Case {
	/**
	 * Default access is limited to Administrators.
	 *
	 * @return void
	 */
	public function test_default_role_access_uses_real_map_meta_cap_filter() {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertTrue( user_can( $administrator, 'sas_access_plugin' ) );
		$this->assertFalse( user_can( $editor, 'sas_access_plugin' ) );
		$this->assertFalse( user_can( $subscriber, 'sas_access_plugin' ) );
	}

	/**
	 * A custom role can be allowed through the documented role filter.
	 *
	 * @return void
	 */
	public function test_custom_role_filter_grants_plugin_access_only() {
		add_role( 'qa_seo_manager', 'QA SEO Manager', array( 'read' => true ) );
		$user_id = self::factory()->user->create( array( 'role' => 'qa_seo_manager' ) );

		add_filter(
			'sas_plugin_access_roles',
			static function ( $roles ) {
				$roles[] = 'qa_seo_manager';
				return $roles;
			}
		);

		$this->assertTrue( user_can( $user_id, 'sas_access_plugin' ) );
		$this->assertFalse( user_can( $user_id, 'manage_options' ) );

		remove_role( 'qa_seo_manager' );
	}

	/**
	 * A custom primitive capability can grant access through the capability filter.
	 *
	 * @return void
	 */
	public function test_custom_capability_filter_grants_plugin_access() {
		add_role(
			'qa_seo_capability_manager',
			'QA SEO Capability Manager',
			array(
				'read' => true,
				'manage_seo_social' => true,
			)
		);
		$user_id = self::factory()->user->create( array( 'role' => 'qa_seo_capability_manager' ) );

		add_filter(
			'sas_plugin_access_capabilities',
			static function () {
				return array( 'manage_seo_social' );
			}
		);

		$this->assertTrue( user_can( $user_id, 'sas_access_plugin' ) );
		$this->assertFalse( user_can( $user_id, 'manage_options' ) );

		remove_role( 'qa_seo_capability_manager' );
	}
}
