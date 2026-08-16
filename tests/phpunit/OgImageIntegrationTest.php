<?php
/**
 * Optimized Open Graph image integration coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Og_Image_Integration_Test extends Seo_And_Social_Test_Case {
	/**
	 * Attachment IDs created by the current test.
	 *
	 * @var array
	 */
	private $attachment_ids = array();

	/**
	 * Temporary files created by the current test.
	 *
	 * @var array
	 */
	private $temporary_files = array();

	/**
	 * Reset temporary-resource tracking.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->attachment_ids = array();
		$this->temporary_files = array();
	}

	/**
	 * Remove test attachments and files.
	 *
	 * @return void
	 */
	public function tear_down() {
		foreach ( $this->attachment_ids as $attachment_id ) {
			sas_delete_optimized_og_image( $attachment_id );
			wp_delete_attachment( $attachment_id, true );
		}

		foreach ( $this->temporary_files as $file ) {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}

		parent::tear_down();
	}

	/**
	 * Disabled optimization neither returns nor regenerates images.
	 *
	 * @return void
	 */
	public function test_disabled_optimization_skips_used_attachment() {
		$attachment_id = $this->create_attachment_without_file();
		$settings = sas_get_default_settings();
		$settings['settings']['enable_og_image_optimization'] = false;
		$settings['seo']['default_og_image_id'] = $attachment_id;
		$this->set_plugin_settings( $settings );

		$this->assertNull( sas_get_optimized_og_image( $attachment_id, true ) );
		$this->assertSame(
			array(
				'generated' => 0,
				'failed' => 0,
				'skipped' => 1,
			),
			sas_regenerate_all_og_images()
		);
	}

	/**
	 * Missing source files return a controlled error and regeneration failure.
	 *
	 * @return void
	 */
	public function test_missing_source_image_returns_controlled_failure() {
		$attachment_id = $this->create_attachment_without_file();
		update_post_meta( $attachment_id, '_wp_attached_file', 'missing/sas-og-source.jpg' );
		$this->enable_optimization( $attachment_id );

		$result = sas_generate_optimized_og_image( $attachment_id );

		$this->assertWPError( $result );
		$this->assertSame( 'sas_missing_source_image', $result->get_error_code() );
		$this->assertSame(
			array(
				'generated' => 0,
				'failed' => 1,
				'skipped' => 0,
			),
			sas_regenerate_all_og_images()
		);
	}

	/**
	 * Used attachment discovery de-duplicates global and local references.
	 *
	 * @return void
	 */
	public function test_used_attachment_ids_are_unique() {
		$attachment_id = $this->create_attachment_without_file();
		$post_id = self::factory()->post->create();
		$settings = sas_get_default_settings();
		$settings['seo']['default_og_image_id'] = $attachment_id;
		$this->set_plugin_settings( $settings );
		update_post_meta( $post_id, SAS_SEO_META_KEY, array( 'og_image_id' => $attachment_id ) );

		$this->assertSame( array( $attachment_id ), sas_get_used_og_attachment_ids() );
	}

	/**
	 * Cleanup deletes only plugin-pattern files inside the uploads directory.
	 *
	 * @return void
	 */
	public function test_cleanup_deletes_safe_generated_file_and_metadata() {
		$attachment_id = $this->create_attachment_without_file();
		$uploads = wp_upload_dir();
		$source_path = trailingslashit( $uploads['path'] ) . 'safe-source.jpg';
		$generated_path = sas_get_optimized_og_image_path( $attachment_id, $source_path );
		wp_mkdir_p( dirname( $generated_path ) );
		file_put_contents( $generated_path, 'fake-webp' );
		$this->temporary_files[] = $generated_path;
		update_post_meta( $attachment_id, SAS_OG_IMAGE_META_KEY, array( 'path' => $generated_path ) );

		$this->assertTrue( sas_is_optimized_og_image_path( $generated_path ) );
		$this->assertTrue( sas_delete_optimized_og_image( $attachment_id ) );
		$this->assertFileDoesNotExist( $generated_path );
		$this->assertSame( '', get_post_meta( $attachment_id, SAS_OG_IMAGE_META_KEY, true ) );
	}

	/**
	 * Cleanup refuses arbitrary paths outside the uploads directory.
	 *
	 * @return void
	 */
	public function test_cleanup_preserves_unsafe_external_file() {
		$attachment_id = $this->create_attachment_without_file();
		$external_file = wp_tempnam( 'external-sas-og-' . $attachment_id . '-1200x630.webp' );
		file_put_contents( $external_file, 'must-remain' );
		$this->temporary_files[] = $external_file;
		update_post_meta( $attachment_id, SAS_OG_IMAGE_META_KEY, array( 'path' => $external_file ) );

		$this->assertFalse( sas_is_optimized_og_image_path( $external_file ) );
		$this->assertFalse( sas_delete_optimized_og_image( $attachment_id ) );
		$this->assertFileExists( $external_file );
		$this->assertSame( '', get_post_meta( $attachment_id, SAS_OG_IMAGE_META_KEY, true ) );
	}

	/**
	 * Generation creates a reusable 1200x630 WebP and cleanup removes it.
	 *
	 * @return void
	 */
	public function test_generation_repeated_execution_and_cleanup() {
		if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagejpeg' ) ) {
			$this->markTestSkipped( 'GD JPEG support is required to create the disposable source image.' );
		}

		if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$this->markTestSkipped( 'The test environment does not support WebP generation.' );
		}

		$attachment_id = $this->create_large_image_attachment();
		$this->enable_optimization( $attachment_id );

		$first = sas_generate_optimized_og_image( $attachment_id );
		$this->assertNotWPError( $first );
		$this->assertFileExists( $first['path'] );
		$this->assertSame( 1200, $first['width'] );
		$this->assertSame( 630, $first['height'] );
		$this->assertSame( 'image/webp', $first['mime'] );
		$this->assertSame( $attachment_id, $first['source_id'] );
		$this->assertSame( $first, sas_get_optimized_og_image( $attachment_id ) );

		$image_size = getimagesize( $first['path'] );
		$this->assertSame( 1200, $image_size[0] );
		$this->assertSame( 630, $image_size[1] );
		$this->assertSame( 'image/webp', $image_size['mime'] );

		$second = sas_generate_optimized_og_image( $attachment_id );
		$this->assertNotWPError( $second );
		$this->assertSame( $first['path'], $second['path'] );
		$this->assertFileExists( $second['path'] );
		$this->assertSame(
			array(
				'generated' => 1,
				'failed' => 0,
				'skipped' => 0,
			),
			sas_regenerate_all_og_images()
		);
		$regenerated = get_post_meta( $attachment_id, SAS_OG_IMAGE_META_KEY, true );
		$this->assertFileExists( $regenerated['path'] );

		$this->assertTrue( sas_delete_optimized_og_image( $attachment_id ) );
		$this->assertFileDoesNotExist( $regenerated['path'] );
		$this->assertNull( sas_get_optimized_og_image( $attachment_id ) );
	}

	/**
	 * Create an attachment without a source file.
	 *
	 * @return int Attachment ID.
	 */
	private function create_attachment_without_file() {
		$attachment_id = self::factory()->post->create(
			array(
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$this->attachment_ids[] = $attachment_id;
		return $attachment_id;
	}

	/**
	 * Create a disposable large JPEG attachment in the test uploads directory.
	 *
	 * @return int Attachment ID.
	 */
	private function create_large_image_attachment() {
		$uploads = wp_upload_dir();
		wp_mkdir_p( $uploads['path'] );
		$source_path = trailingslashit( $uploads['path'] ) . 'sas-og-source-' . wp_generate_uuid4() . '.jpg';
		$image = imagecreatetruecolor( 1600, 900 );
		$background = imagecolorallocate( $image, 36, 99, 160 );
		imagefill( $image, 0, 0, $background );
		imagejpeg( $image, $source_path, 90 );
		imagedestroy( $image );
		$this->temporary_files[] = $source_path;

		$attachment_id = wp_insert_attachment(
			array(
				'post_title' => 'Disposable OG source',
				'post_status' => 'inherit',
				'post_mime_type' => 'image/jpeg',
			),
			$source_path
		);
		update_attached_file( $attachment_id, $source_path );
		$this->attachment_ids[] = $attachment_id;
		return $attachment_id;
	}

	/**
	 * Enable optimization and optionally register a global attachment reference.
	 *
	 * @param int $attachment_id Default OG attachment ID.
	 * @return void
	 */
	private function enable_optimization( $attachment_id = 0 ) {
		$settings = sas_get_default_settings();
		$settings['settings']['enable_og_image_optimization'] = true;
		$settings['seo']['default_og_image_id'] = $attachment_id;
		$this->set_plugin_settings( $settings );
	}
}
