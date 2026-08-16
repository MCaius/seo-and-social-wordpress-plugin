<?php
/**
 * Per-content SEO and FAQ integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Content_Meta_Integration_Test extends Seo_And_Social_Test_Case {
	/**
	 * WordPress autosave requests must preserve existing SEO and FAQ metadata.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_autosave_does_not_overwrite_content_metadata() {
		define( 'DOING_AUTOSAVE', true );
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		wp_set_current_user( $administrator );
		$this->set_plugin_settings( sas_get_default_settings() );

		$original_seo = array( 'seo_title' => 'Original SEO title' );
		$original_faq = array(
			array(
				'question' => 'Original?',
				'answer' => 'Original answer',
				'enabled' => true,
				'position' => 1,
			),
		);
		update_post_meta( $post_id, SAS_SEO_META_KEY, $original_seo );
		update_post_meta( $post_id, SAS_FAQ_META_KEY, $original_faq );

		$_POST['sas_seo_nonce'] = wp_create_nonce( 'sas_save_seo_overrides' );
		$_POST['sas_seo'] = array( 'seo_title' => 'Autosaved SEO title' );
		$_POST['sas_faq_nonce'] = wp_create_nonce( 'sas_save_faq_items' );
		$_POST['sas_faq'] = array(
			array(
				'question' => 'Autosaved?',
				'answer' => 'Autosaved answer',
				'enabled' => '1',
				'position' => '1',
			),
		);

		sas_save_seo_meta_box( $post_id );
		sas_save_faq_meta_box( $post_id );

		$this->assertSame( $original_seo, get_post_meta( $post_id, SAS_SEO_META_KEY, true ) );
		$this->assertSame( $original_faq, get_post_meta( $post_id, SAS_FAQ_META_KEY, true ) );
	}

	/**
	 * Missing and invalid SEO nonces must preserve existing metadata.
	 *
	 * @dataProvider seo_nonce_provider
	 *
	 * @param string|null $nonce Nonce value, or null when omitted.
	 * @return void
	 */
	public function test_seo_meta_handler_rejects_missing_or_invalid_nonce( $nonce ) {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		wp_set_current_user( $administrator );
		$this->set_plugin_settings( sas_get_default_settings() );
		update_post_meta( $post_id, SAS_SEO_META_KEY, array( 'seo_title' => 'Original title' ) );

		if ( null !== $nonce ) {
			$_POST['sas_seo_nonce'] = $nonce;
		}
		$_POST['sas_seo'] = array( 'seo_title' => 'Changed title' );

		sas_save_seo_meta_box( $post_id );

		$saved = get_post_meta( $post_id, SAS_SEO_META_KEY, true );
		$this->assertSame( 'Original title', $saved['seo_title'] );
	}

	/**
	 * SEO nonce cases rejected by the save handler.
	 *
	 * @return array
	 */
	public function seo_nonce_provider() {
		return array(
			'missing nonce' => array( null ),
			'invalid nonce' => array( 'invalid-seo-nonce' ),
		);
	}

	/**
	 * SEO metadata is sanitized and saved by the real handler.
	 *
	 * @return void
	 */
	public function test_seo_meta_handler_saves_sanitized_values() {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		wp_set_current_user( $administrator );
		$this->set_plugin_settings( sas_get_default_settings() );

		$_POST['sas_seo_nonce'] = wp_create_nonce( 'sas_save_seo_overrides' );
		$_POST['sas_seo'] = array(
			'seo_title' => '<b>Local title</b>',
			'seo_description' => '<script>alert(1)</script>Description',
			'canonical_url' => 'https://example.test/page',
			'robots' => 'noindex,follow',
			'schema_type' => 'Article',
			'custom_schema_json' => '{"@type":"Article"}',
		);

		sas_save_seo_meta_box( $post_id );
		$saved = get_post_meta( $post_id, SAS_SEO_META_KEY, true );

		$this->assertSame( 'Local title', $saved['seo_title'] );
		$this->assertStringNotContainsString( '<script>', $saved['seo_description'] );
		$this->assertSame( 'https://example.test/page', $saved['canonical_url'] );
		$this->assertSame( 'noindex,follow', $saved['robots'] );
		$this->assertSame( '{"@type":"Article"}', $saved['custom_schema_json'] );
	}

	/**
	 * Clearing the SEO form replaces previous values with safe empty defaults.
	 *
	 * @return void
	 */
	public function test_seo_meta_handler_clears_previous_values() {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		wp_set_current_user( $administrator );
		$this->set_plugin_settings( sas_get_default_settings() );
		update_post_meta( $post_id, SAS_SEO_META_KEY, array( 'seo_title' => 'Previous title' ) );

		$_POST['sas_seo_nonce'] = wp_create_nonce( 'sas_save_seo_overrides' );
		$_POST['sas_seo'] = array();
		sas_save_seo_meta_box( $post_id );

		$this->assertSame( sas_get_default_post_seo_overrides(), get_post_meta( $post_id, SAS_SEO_META_KEY, true ) );
	}

	/**
	 * SEO metadata is not saved for a disabled post type or a revision.
	 *
	 * @dataProvider disallowed_seo_post_provider
	 *
	 * @param string $post_type Post type to exercise.
	 * @return void
	 */
	public function test_seo_meta_handler_rejects_disallowed_content_types( $post_type ) {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => $post_type ) );
		wp_set_current_user( $administrator );
		$settings = sas_get_default_settings();
		$settings['settings']['seo_post_types'] = array( 'page' );
		$this->set_plugin_settings( $settings );

		$_POST['sas_seo_nonce'] = wp_create_nonce( 'sas_save_seo_overrides' );
		$_POST['sas_seo'] = array( 'seo_title' => 'Must not save' );
		sas_save_seo_meta_box( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, SAS_SEO_META_KEY, true ) );
	}

	/**
	 * Content types rejected by the SEO save handler.
	 *
	 * @return array
	 */
	public function disallowed_seo_post_provider() {
		return array(
			'disabled post type' => array( 'post' ),
			'revision' => array( 'revision' ),
		);
	}

	/**
	 * FAQ output contains only complete enabled rows sorted by position.
	 *
	 * @return void
	 */
	public function test_faq_handler_filters_and_orders_public_rows() {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		wp_set_current_user( $administrator );
		$this->set_plugin_settings( sas_get_default_settings() );

		$_POST['sas_faq_nonce'] = wp_create_nonce( 'sas_save_faq_items' );
		$_POST['sas_faq'] = array(
			array( 'question' => 'Third?', 'answer' => 'Third answer', 'enabled' => '1', 'position' => '30' ),
			array( 'question' => 'First?', 'answer' => 'First answer', 'enabled' => '1', 'position' => '10' ),
			array( 'question' => 'Disabled?', 'answer' => 'Hidden', 'position' => '5' ),
			array( 'question' => 'Incomplete?', 'answer' => '', 'enabled' => '1', 'position' => '1' ),
		);

		sas_save_faq_meta_box( $post_id );
		$public = sas_get_public_faq_items( $post_id );

		$this->assertCount( 2, $public );
		$this->assertSame( 'First?', $public[0]['question'] );
		$this->assertSame( 'Third?', $public[1]['question'] );
	}

	/**
	 * FAQ HTML follows the configured allow-list policy.
	 *
	 * @return void
	 */
	public function test_faq_html_policy_preserves_safe_html_and_removes_scripts() {
		$settings = sas_get_default_settings();
		$settings['settings']['faq_allow_html'] = true;
		$this->set_plugin_settings( $settings );

		$items = sas_sanitize_faq_items(
			array(
				array(
					'question' => '<b>Safe question?</b>',
					'answer' => '<strong>Safe answer</strong><script>alert(1)</script>',
					'enabled' => '1',
				),
			)
		);

		$this->assertSame( 'Safe question?', $items[0]['question'] );
		$this->assertStringContainsString( '<strong>Safe answer</strong>', $items[0]['answer'] );
		$this->assertStringNotContainsString( '<script>', $items[0]['answer'] );
	}

	/**
	 * FAQ answers become plain text when HTML is disabled.
	 *
	 * @return void
	 */
	public function test_faq_html_policy_strips_markup_when_disabled() {
		$settings = sas_get_default_settings();
		$settings['settings']['faq_allow_html'] = false;
		$this->set_plugin_settings( $settings );

		$items = sas_sanitize_faq_items(
			array(
				array(
					'question' => 'Plain?',
					'answer' => '<strong>Plain answer</strong>',
					'enabled' => '1',
				),
			)
		);

		$this->assertSame( 'Plain answer', $items[0]['answer'] );
	}

	/**
	 * Missing and invalid FAQ nonces must preserve existing metadata.
	 *
	 * @dataProvider faq_nonce_provider
	 *
	 * @param string|null $nonce Nonce value, or null when omitted.
	 * @return void
	 */
	public function test_faq_meta_handler_rejects_missing_or_invalid_nonce( $nonce ) {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		wp_set_current_user( $administrator );
		$this->set_plugin_settings( sas_get_default_settings() );
		$original = array(
			array(
				'question' => 'Original?',
				'answer' => 'Original answer',
				'enabled' => true,
				'position' => 1,
			),
		);
		update_post_meta( $post_id, SAS_FAQ_META_KEY, $original );

		if ( null !== $nonce ) {
			$_POST['sas_faq_nonce'] = $nonce;
		}
		$_POST['sas_faq'] = array(
			array(
				'question' => 'Changed?',
				'answer' => 'Changed answer',
				'enabled' => '1',
				'position' => '1',
			),
		);

		sas_save_faq_meta_box( $post_id );

		$this->assertSame( $original, get_post_meta( $post_id, SAS_FAQ_META_KEY, true ) );
	}

	/**
	 * FAQ nonce cases rejected by the save handler.
	 *
	 * @return array
	 */
	public function faq_nonce_provider() {
		return array(
			'missing nonce' => array( null ),
			'invalid nonce' => array( 'invalid-faq-nonce' ),
		);
	}

	/**
	 * A user without edit permission cannot overwrite existing SEO metadata.
	 *
	 * @return void
	 */
	public function test_unauthorized_user_cannot_overwrite_seo_meta() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		update_post_meta( $post_id, SAS_SEO_META_KEY, array( 'seo_title' => 'Original title' ) );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$_POST['sas_seo_nonce'] = wp_create_nonce( 'sas_save_seo_overrides' );
		$_POST['sas_seo'] = array( 'seo_title' => 'Unauthorized title' );
		sas_save_seo_meta_box( $post_id );

		$saved = get_post_meta( $post_id, SAS_SEO_META_KEY, true );
		$this->assertSame( 'Original title', $saved['seo_title'] );
	}

	/**
	 * A user without edit permission cannot overwrite existing FAQ metadata.
	 *
	 * @return void
	 */
	public function test_unauthorized_user_cannot_overwrite_faq_meta() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$original = array(
			array(
				'question' => 'Original?',
				'answer' => 'Original answer',
				'enabled' => true,
				'position' => 1,
			),
		);
		update_post_meta( $post_id, SAS_FAQ_META_KEY, $original );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$_POST['sas_faq_nonce'] = wp_create_nonce( 'sas_save_faq_items' );
		$_POST['sas_faq'] = array(
			array(
				'question' => 'Unauthorized?',
				'answer' => 'Unauthorized answer',
				'enabled' => '1',
				'position' => '1',
			),
		);
		sas_save_faq_meta_box( $post_id );

		$this->assertSame( $original, get_post_meta( $post_id, SAS_FAQ_META_KEY, true ) );
	}
}
