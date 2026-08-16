<?php
/**
 * Uninstall and explicit data-cleanup integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Uninstall_Integration_Test extends Seo_And_Social_Test_Case {
	/**
	 * Disposable attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id = 0;

	/**
	 * Disposable generated-file path.
	 *
	 * @var string
	 */
	private $generated_path = '';

	/**
	 * Captured cleanup redirect.
	 *
	 * @var string
	 */
	private $redirect_location = '';

	/**
	 * Reset test resources and capture redirects.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->attachment_id = 0;
		$this->generated_path = '';
		$this->redirect_location = '';
		add_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );
	}

	/**
	 * Remove disposable files and attachments.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );

		if ( $this->attachment_id ) {
			sas_delete_optimized_og_image( $this->attachment_id );
			wp_delete_attachment( $this->attachment_id, true );
		}

		if ( $this->generated_path && file_exists( $this->generated_path ) ) {
			wp_delete_file( $this->generated_path );
		}

		parent::tear_down();
	}

	/**
	 * Store a redirect and prevent CLI header output.
	 *
	 * @param string $location Redirect location.
	 * @return false
	 */
	public function capture_redirect( $location ) {
		$this->redirect_location = $location;
		return false;
	}

	/**
	 * Standard WordPress uninstall preserves settings, metadata, and generated files.
	 *
	 * @return void
	 */
	public function test_standard_uninstall_preserves_plugin_data() {
		$post_id = $this->seed_plugin_data();

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		require SAS_PLUGIN_DIR . 'uninstall.php';

		$settings = get_option( SAS_OPTION_NAME );
		$this->assertSame( 'preserve@example.test', $settings['social']['email'] );
		$this->assertSame( 'Preserved SEO', get_post_meta( $post_id, SAS_SEO_META_KEY, true )['seo_title'] );
		$this->assertSame( 'Preserved FAQ?', get_post_meta( $post_id, SAS_FAQ_META_KEY, true )[0]['question'] );
		$this->assertSame( $this->generated_path, get_post_meta( $this->attachment_id, SAS_OG_IMAGE_META_KEY, true )['path'] );
		$this->assertFileExists( $this->generated_path );
	}

	/**
	 * Explicit Administrator cleanup deletes every plugin-owned data category.
	 *
	 * @return void
	 */
	public function test_explicit_delete_all_data_removes_plugin_data() {
		$post_id = $this->seed_plugin_data();
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $administrator );
		$nonce = wp_create_nonce( 'sas_delete_all_data' );
		$_POST['sas_delete_all_data_nonce'] = $nonce;
		$_REQUEST['sas_delete_all_data_nonce'] = $nonce;

		sas_handle_delete_all_data();

		$this->assertFalse( get_option( SAS_OPTION_NAME, false ) );
		$this->assertSame( '', get_post_meta( $post_id, SAS_SEO_META_KEY, true ) );
		$this->assertSame( '', get_post_meta( $post_id, SAS_FAQ_META_KEY, true ) );
		$this->assertSame( '', get_post_meta( $this->attachment_id, SAS_OG_IMAGE_META_KEY, true ) );
		$this->assertFileDoesNotExist( $this->generated_path );
		$this->assertStringContainsString( 'sas_message=deleted', $this->redirect_location );
	}

	/**
	 * Create all plugin-owned data categories used by uninstall tests.
	 *
	 * @return int Content post ID.
	 */
	private function seed_plugin_data() {
		$settings = sas_get_default_settings();
		$settings['social']['email'] = 'preserve@example.test';
		$this->set_plugin_settings( $settings );

		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, SAS_SEO_META_KEY, array( 'seo_title' => 'Preserved SEO' ) );
		update_post_meta(
			$post_id,
			SAS_FAQ_META_KEY,
			array(
				array(
					'question' => 'Preserved FAQ?',
					'answer' => 'Preserved answer',
					'enabled' => true,
					'position' => 1,
				),
			)
		);

		$this->attachment_id = self::factory()->post->create(
			array(
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$uploads = wp_upload_dir();
		$source_path = trailingslashit( $uploads['path'] ) . 'uninstall-source.jpg';
		$this->generated_path = sas_get_optimized_og_image_path( $this->attachment_id, $source_path );
		wp_mkdir_p( dirname( $this->generated_path ) );
		file_put_contents( $this->generated_path, 'fake-webp' );
		update_post_meta(
			$this->attachment_id,
			SAS_OG_IMAGE_META_KEY,
			array(
				'path' => $this->generated_path,
				'source_id' => $this->attachment_id,
			)
		);

		return $post_id;
	}
}
