<?php
/**
 * Per-content SEO and FAQ integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Content_Meta_Integration_Test extends Seo_And_Social_Test_Case {
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
}
