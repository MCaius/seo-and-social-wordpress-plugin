<?php
/**
 * Admin menu and settings screens.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register top-level admin menu.
 *
 * @return void
 */
function sas_register_admin_menu() {
	add_menu_page(
		__( 'Seo & Social', 'seo-and-social' ),
		__( 'Seo & Social', 'seo-and-social' ),
		'sas_access_plugin',
		'seo-and-social',
		'sas_render_admin_page',
		'dashicons-share',
		58
	);
}

/**
 * Render admin settings page.
 *
 * @return void
 */
function sas_render_admin_page() {
	if ( ! current_user_can( 'sas_access_plugin' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage these settings.', 'seo-and-social' ) );
	}

	$settings = sas_get_settings();
	$can_manage_settings = current_user_can( 'manage_options' );
	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'social';
	$allowed_tabs = $can_manage_settings ? array( 'social', 'seo', 'llms', 'settings' ) : array( 'social', 'seo', 'llms' );

	if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
		$active_tab = 'social';
	}

	$message = isset( $_GET['sas_message'] ) ? sanitize_key( wp_unslash( $_GET['sas_message'] ) ) : '';
	$notice = isset( $_GET['sas_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['sas_notice'] ) ) : '';
	$notice_status = isset( $_GET['sas_status'] ) ? sanitize_key( wp_unslash( $_GET['sas_status'] ) ) : 'success';
	$allowed_notice_statuses = array( 'success', 'warning', 'error', 'info' );

	if ( ! in_array( $notice_status, $allowed_notice_statuses, true ) ) {
		$notice_status = 'success';
	}
	?>
	<div class="wrap sas-admin-page">
		<h1><?php echo esc_html__( 'Seo & Social', 'seo-and-social' ); ?></h1>

		<?php if ( $message === 'saved' ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html__( 'Settings saved.', 'seo-and-social' ); ?></p>
			</div>
		<?php elseif ( $message === 'json_error' ) : ?>
			<div class="notice notice-warning is-dismissible">
				<p><?php echo esc_html__( 'Settings saved, but one or more invalid JSON values were removed from public output.', 'seo-and-social' ); ?></p>
			</div>
		<?php elseif ( $message === 'deleted' ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html__( 'All Seo & Social plugin data was deleted.', 'seo-and-social' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice_status ); ?> is-dismissible">
				<p><?php echo esc_html( $notice ); ?></p>
			</div>
		<?php endif; ?>

		<?php sas_render_how_to_use(); ?>

		<nav class="nav-tab-wrapper" aria-label="<?php echo esc_attr__( 'Seo & Social tabs', 'seo-and-social' ); ?>">
			<?php sas_render_admin_tab_link( 'social', __( 'Social', 'seo-and-social' ), $active_tab ); ?>
			<?php sas_render_admin_tab_link( 'seo', __( 'SEO', 'seo-and-social' ), $active_tab ); ?>
			<?php sas_render_admin_tab_link( 'llms', __( 'LLMs.txt', 'seo-and-social' ), $active_tab ); ?>
			<?php if ( $can_manage_settings ) : ?>
				<?php sas_render_admin_tab_link( 'settings', __( 'Settings', 'seo-and-social' ), $active_tab ); ?>
			<?php endif; ?>
		</nav>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sas-settings-form">
			<input type="hidden" name="action" value="sas_save_settings">
			<input type="hidden" name="sas_active_tab" value="<?php echo esc_attr( $active_tab ); ?>">
			<?php wp_nonce_field( 'sas_save_settings', 'sas_settings_nonce' ); ?>

			<?php
			if ( $active_tab === 'seo' ) {
				sas_render_seo_tab( $settings );
			} elseif ( $active_tab === 'llms' ) {
				sas_render_llms_tab( $settings );
			} elseif ( $active_tab === 'settings' && $can_manage_settings ) {
				sas_render_settings_tab( $settings );
			} else {
				sas_render_social_tab( $settings );
			}
			?>

			<?php submit_button( __( 'Save settings', 'seo-and-social' ) ); ?>
		</form>

		<form id="sas-delete-all-data-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sas_delete_all_data">
			<?php wp_nonce_field( 'sas_delete_all_data', 'sas_delete_all_data_nonce' ); ?>
		</form>

		<form id="sas-regenerate-og-images-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sas_regenerate_og_images">
			<?php wp_nonce_field( 'sas_regenerate_og_images', 'sas_regenerate_og_images_nonce' ); ?>
		</form>

		<form id="sas-delete-optimized-og-images-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sas_delete_optimized_og_images">
			<?php wp_nonce_field( 'sas_delete_optimized_og_images', 'sas_delete_optimized_og_images_nonce' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Render How to use toggles.
 *
 * @return void
 */
function sas_render_how_to_use() {
	$can_manage_settings = current_user_can( 'manage_options' );
	?>
	<details class="sas-howto">
		<summary><?php echo esc_html__( 'How to use', 'seo-and-social' ); ?></summary>

		<div class="sas-howto-content">
			<details class="sas-howto-toggle">
				<summary><?php echo esc_html__( 'General setup', 'seo-and-social' ); ?></summary>

				<div class="sas-howto-toggle-content">
					<p><?php echo wp_kses_post( __( 'Start in <strong>Settings</strong>. Enable the modules you want to expose, choose the post types that should receive SEO and FAQ boxes, and keep Media unchecked unless you intentionally want plugin fields on attachments.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'Use the <strong>Social</strong> tab for public contact and social profile data. Use the <strong>SEO</strong> tab for global fallbacks such as site name, default meta title, default description, default OG image, organization fields, and schema defaults.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'Use the <strong>LLMs.txt</strong> tab to manage structured content that your frontend can use to serve a public <code>/llms.txt</code> file.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'After that, edit individual pages, posts, or CPT items. Fill only the fields that should override the global defaults. Empty local SEO fields are treated as “use the global value”. FAQ items are saved per content item and exposed only when enabled.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'If OG image optimization is enabled, images selected through the plugin can get a separate 1200x630 WebP copy. Originals remain untouched. Use <strong>Regenerate OG images</strong> after enabling optimization on an existing site, and <strong>Delete generated WebP images</strong> when you want to clear only those generated files.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'Administrators can access the global Seo & Social admin pages, configure plugin settings, choose enabled post types, manage REST names, control public endpoint behavior, and run generated-image maintenance tools. Editors do not see the global plugin menu by default, but they can still use the SEO/FAQ boxes on content they are allowed to edit.', 'seo-and-social' ) ); ?></p>
				</div>
			</details>

			<?php if ( $can_manage_settings ) : ?>
			<details class="sas-howto-toggle">
				<summary><?php echo esc_html__( 'API and frontend usage', 'seo-and-social' ); ?></summary>

				<div class="sas-howto-toggle-content">
					<p><?php echo wp_kses_post( __( 'The global settings endpoint defaults to <code>/wp-json/headless-seo/v1/site-settings</code>. It returns <code>social</code> and global <code>seo</code> data for the frontend.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'Per-content REST responses expose <code>seo_overrides</code>, <code>seo_resolved</code>, and <code>faq_items</code> by default. The override and FAQ field names can be changed in Settings, but <code>seo_resolved</code> is reserved and always uses that name.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( '<code>seo_overrides</code> contains only what was saved on that specific page, post, or CPT item. <code>seo_resolved</code> is the final SEO payload: local overrides first, then global defaults as fallback. Frontends should usually render metadata from <code>seo_resolved</code>.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'For OG images, use <code>og_image_url</code> as the preferred final URL. Keep <code>og_image_original_url</code> as the source/fallback reference, and use <code>og_image_optimized</code> when you need width, height, or MIME details for the generated WebP.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'If the public endpoint is enabled, unauthenticated frontend requests can read the settings endpoint and are protected by a lightweight rate limit. If it is disabled, the settings endpoint requires an authenticated administrator.', 'seo-and-social' ) ); ?></p>
				</div>
			</details>

			<details class="sas-howto-toggle">
				<summary><?php echo esc_html__( 'How data is handled in the database', 'seo-and-social' ); ?></summary>

				<div class="sas-howto-toggle-content">
					<p><?php echo wp_kses_post( __( 'Global plugin settings are stored in <code>wp_options</code> under <code>sas_settings</code>, with autoload disabled. This includes Social fields, global SEO/schema fields, enabled post types, REST names, and feature toggles.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'Per-content SEO data is stored in <code>wp_postmeta</code> under <code>_sas_seo_overrides</code>. Per-content FAQ data is stored in <code>wp_postmeta</code> under <code>_sas_faq_items</code>. <code>seo_resolved</code> is not stored; it is computed when the REST response is requested.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'Generated OG WebP metadata is stored on the source attachment under <code>_sas_og_image_1200x630</code>. The generated WebP file is saved beside the original upload file. Deleting the source attachment, deleting generated WebP images, or deleting all plugin data removes the generated file metadata and files.', 'seo-and-social' ) ); ?></p>
					<p><?php echo wp_kses_post( __( 'The Danger zone button deletes <code>sas_settings</code>, plugin SEO/FAQ post meta, and generated WebP OG files. It does not delete posts, pages, CPT items, users, terms, or original Media Library files.', 'seo-and-social' ) ); ?></p>
				</div>
			</details>
			<?php endif; ?>
		</div>
	</details>
	<?php
}

/**
 * Render LLMs.txt tab.
 *
 * @param array $settings Settings.
 * @return void
 */
function sas_render_llms_tab( $settings ) {
	$llms = $settings['llms'];
	?>
	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'LLMs.txt content', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
			<?php
			sas_checkbox_field(
				'sas_settings[llms][enabled]',
				__( 'Enable LLMs.txt data', 'seo-and-social' ),
				$llms['enabled'],
				array(
					'info' => __( 'When enabled, the JSON API marks the LLMs.txt payload as enabled. The frontend should use this to decide whether to serve /llms.txt publicly.', 'seo-and-social' ),
				)
			);
			sas_textarea_field(
				'sas_settings[llms][site_summary]',
				__( 'Site summary', 'seo-and-social' ),
				$llms['site_summary'],
				array(
					'rows' => 5,
					'info' => __( 'Short human-readable summary of what this site offers and what AI tools should understand about it.', 'seo-and-social' ),
				)
			);
			?>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Allowed / recommended pages', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
			<p class="description"><?php echo esc_html__( 'Pages the frontend should include as recommended resources in llms.txt.', 'seo-and-social' ); ?></p>
			<div class="sas-repeatable" data-sas-repeater="llms-recommended-pages">
				<div class="sas-repeatable-rows" data-sas-rows>
					<?php foreach ( $llms['recommended_pages'] as $index => $row ) : ?>
						<?php sas_render_llms_recommended_page_row( (string) $index, $row ); ?>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button" data-sas-add-row data-template="tmpl-sas-llms-recommended-page-row">
					<?php echo esc_html__( 'Add row', 'seo-and-social' ); ?>
				</button>
			</div>
			<script type="text/template" id="tmpl-sas-llms-recommended-page-row">
				<?php sas_render_llms_recommended_page_row( '__INDEX__', array() ); ?>
			</script>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Disallowed / ignored sections', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
			<p class="description"><?php echo esc_html__( 'Sections that should be described as ignored, low priority, private, duplicated, or not useful for AI context.', 'seo-and-social' ); ?></p>
			<div class="sas-repeatable" data-sas-repeater="llms-ignored-sections">
				<div class="sas-repeatable-rows" data-sas-rows>
					<?php foreach ( $llms['ignored_sections'] as $index => $row ) : ?>
						<?php sas_render_llms_ignored_section_row( (string) $index, $row ); ?>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button" data-sas-add-row data-template="tmpl-sas-llms-ignored-section-row">
					<?php echo esc_html__( 'Add row', 'seo-and-social' ); ?>
				</button>
			</div>
			<script type="text/template" id="tmpl-sas-llms-ignored-section-row">
				<?php sas_render_llms_ignored_section_row( '__INDEX__', array() ); ?>
			</script>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Custom raw llms.txt content', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
			<?php
			sas_textarea_field(
				'sas_settings[llms][custom_content]',
				__( 'Custom content', 'seo-and-social' ),
				$llms['custom_content'],
				array(
					'rows' => 8,
					'info' => __( 'Optional raw Markdown-style text appended to the generated llms.txt preview and API payload.', 'seo-and-social' ),
				)
			);
			?>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Preview', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
			<p class="description"><?php echo esc_html__( 'This is the current rendered_txt value that the JSON endpoint returns for the frontend.', 'seo-and-social' ); ?></p>
			<pre class="sas-llms-preview"><code><?php echo esc_html( sas_render_llms_txt( $llms ) ); ?></code></pre>
		</div>
	</details>
	<?php
}

/**
 * Render a tab link.
 *
 * @param string $tab Tab key.
 * @param string $label Tab label.
 * @param string $active_tab Active tab.
 * @return void
 */
function sas_render_admin_tab_link( $tab, $label, $active_tab ) {
	$url = add_query_arg(
		array(
			'page' => 'seo-and-social',
			'tab' => $tab,
		),
		admin_url( 'admin.php' )
	);
	?>
	<a class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
		<?php echo esc_html( $label ); ?>
	</a>
	<?php
}

/**
 * Render Social tab.
 *
 * @param array $settings Settings.
 * @return void
 */
function sas_render_social_tab( $settings ) {
	$social = $settings['social'];
	?>
	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Social links', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<?php
		sas_text_field(
			'sas_settings[social][email]',
			__( 'Primary email', 'seo-and-social' ),
			$social['email'],
			array(
				'type' => 'email',
				'info' => __( 'Exposed as the primary contact email for the frontend.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[social][phone_whatsapp]',
			__( 'Phone / WhatsApp', 'seo-and-social' ),
			$social['phone_whatsapp'],
			array(
				'placeholder' => '+1234567890',
				'info' => __( 'Use an international phone format. The frontend can decide whether to render a call or WhatsApp link.', 'seo-and-social' ),
			)
		);
		sas_text_field( 'sas_settings[social][facebook_url]', __( 'Facebook URL', 'seo-and-social' ), $social['facebook_url'], array( 'type' => 'url' ) );
		sas_text_field( 'sas_settings[social][instagram_url]', __( 'Instagram URL', 'seo-and-social' ), $social['instagram_url'], array( 'type' => 'url' ) );
		sas_text_field( 'sas_settings[social][tiktok_url]', __( 'TikTok URL', 'seo-and-social' ), $social['tiktok_url'], array( 'type' => 'url' ) );
		sas_text_field( 'sas_settings[social][youtube_url]', __( 'YouTube URL', 'seo-and-social' ), $social['youtube_url'], array( 'type' => 'url' ) );
		?>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Extra social links', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<p class="description"><?php echo esc_html__( 'Only rows with key, label, and valid URL are exposed in the public API.', 'seo-and-social' ); ?></p>
		<div class="sas-repeatable" data-sas-repeater="extra-social-links">
			<div class="sas-repeatable-rows" data-sas-rows>
				<?php foreach ( $social['extra_links'] as $index => $row ) : ?>
					<?php sas_render_extra_social_row( (string) $index, $row ); ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button" data-sas-add-row data-template="tmpl-sas-extra-social-row">
				<?php echo esc_html__( 'Add row', 'seo-and-social' ); ?>
			</button>
		</div>
		<script type="text/template" id="tmpl-sas-extra-social-row">
			<?php sas_render_extra_social_row( '__INDEX__', array() ); ?>
		</script>
		</div>
	</details>
	<?php
}

/**
 * Render SEO tab.
 *
 * @param array $settings Settings.
 * @return void
 */
function sas_render_seo_tab( $settings ) {
	$seo = $settings['seo'];
	$default_og_image_url = $seo['default_og_image'];
	$default_og_image_id_field_id = sas_field_id( 'sas_settings[seo][default_og_image_id]' );
	$default_robots = '';

	if ( isset( $seo['default_robots'] ) && in_array( $seo['default_robots'], array_keys( sas_get_robots_options() ), true ) ) {
		$default_robots = $seo['default_robots'];
	}

	if ( ! $default_og_image_url && ! empty( $seo['default_og_image_id'] ) ) {
		$attachment_url = wp_get_attachment_image_url( (int) $seo['default_og_image_id'], 'full' );
		$default_og_image_url = $attachment_url ? $attachment_url : '';
	}
	?>
	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'SEO defaults', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<?php
		sas_text_field(
			'sas_settings[seo][site_name]',
			__( 'Site name', 'seo-and-social' ),
			$seo['site_name'],
			array(
				'info' => __( 'Used as a global site name fallback for frontend metadata.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][default_meta_title]',
			__( 'Default meta title', 'seo-and-social' ),
			$seo['default_meta_title'],
			array(
				'info' => __( 'Fallback title when content does not provide a custom SEO title.', 'seo-and-social' ),
			)
		);
		sas_textarea_field(
			'sas_settings[seo][default_meta_description]',
			__( 'Default meta description', 'seo-and-social' ),
			$seo['default_meta_description'],
			array(
				'rows' => 3,
				'info' => __( 'Fallback description when content does not provide a custom SEO description.', 'seo-and-social' ),
			)
		);
		?>
		<div class="sas-field">
			<div class="sas-label-row">
				<label for="sas_settings_seo_default_robots"><?php echo esc_html__( 'Default robots', 'seo-and-social' ); ?></label>
				<?php sas_info_button( 'sas_settings_seo_default_robots', __( 'In SEO, if you do not send a robots meta tag at all, the normal search engine behavior is effectively index,follow. Leave this empty for that default behavior. This is useful for testing or staging websites where you may need noindex generally.', 'seo-and-social' ) ); ?>
			</div>
			<select id="sas_settings_seo_default_robots" name="sas_settings[seo][default_robots]">
				<?php foreach ( sas_get_robots_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $default_robots, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php sas_info_panel( 'sas_settings_seo_default_robots', __( 'In SEO, if you do not send a robots meta tag at all, the normal search engine behavior is effectively index,follow. Leave this empty for that default behavior. This is useful for testing or staging websites where you may need noindex generally.', 'seo-and-social' ) ); ?>
		</div>
		<input
			type="hidden"
			id="<?php echo esc_attr( $default_og_image_id_field_id ); ?>"
			name="sas_settings[seo][default_og_image_id]"
			value="<?php echo esc_attr( (string) $seo['default_og_image_id'] ); ?>"
		>
		<?php
		sas_text_field(
			'sas_settings[seo][default_og_image]',
			__( 'Default OG image URL', 'seo-and-social' ),
			$default_og_image_url,
			array(
				'type' => 'url',
				'media' => true,
				'media_id_target' => $default_og_image_id_field_id,
				'info' => __( 'Default image URL for social sharing when content does not provide its own image.', 'seo-and-social' ),
			)
		);
		?>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Organization schema data', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<?php
		sas_text_field(
			'sas_settings[seo][schema_type]',
			__( 'Business / organization schema type', 'seo-and-social' ),
			$seo['schema_type'],
			array(
				'placeholder' => 'Organization',
				'info' => __( 'Schema.org type such as Organization, LocalBusiness, MedicalBusiness, or Dentist. The frontend decides how to render JSON-LD.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][organization_name]',
			__( 'Organization name', 'seo-and-social' ),
			$seo['organization_name'],
			array(
				'info' => __( 'The public business or organization name used in Organization or LocalBusiness schema.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][logo_url]',
			__( 'Logo URL', 'seo-and-social' ),
			$seo['logo_url'],
			array(
				'type' => 'url',
				'media' => true,
				'info' => __( 'Logo image URL for structured data. Use a clear brand mark from the Media Library when possible.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][website_url]',
			__( 'Website URL', 'seo-and-social' ),
			$seo['website_url'],
			array(
				'type' => 'url',
				'info' => __( 'Canonical public website URL for this organization. Usually the frontend site URL.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][address]',
			__( 'Address', 'seo-and-social' ),
			$seo['address'],
			array(
				'info' => __( 'Street address or public location address used in local business schema.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][city]',
			__( 'City', 'seo-and-social' ),
			$seo['city'],
			array(
				'info' => __( 'City or locality for the organization address.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][country]',
			__( 'Country', 'seo-and-social' ),
			$seo['country'],
			array(
				'info' => __( 'Country for the organization address. Use the public-facing country name or ISO code your frontend expects.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][postal_code]',
			__( 'Postal code', 'seo-and-social' ),
			$seo['postal_code'],
			array(
				'info' => __( 'Postal or ZIP code for the organization address.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][latitude]',
			__( 'Latitude', 'seo-and-social' ),
			$seo['latitude'],
			array(
				'info' => __( 'Optional geographic latitude for LocalBusiness schema. Use decimal degrees, for example 40.7128.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][longitude]',
			__( 'Longitude', 'seo-and-social' ),
			$seo['longitude'],
			array(
				'info' => __( 'Optional geographic longitude for LocalBusiness schema. Use decimal degrees, for example -74.0060.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][opening_hours]',
			__( 'Opening hours', 'seo-and-social' ),
			$seo['opening_hours'],
			array(
				'placeholder' => 'Mo-Fr 09:00-18:00',
				'info' => __( 'Opening hours in a compact schema-friendly format, for example Mo-Fr 09:00-18:00.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[seo][price_range]',
			__( 'Price range', 'seo-and-social' ),
			$seo['price_range'],
			array(
				'placeholder' => '$$',
				'info' => __( 'Optional price range indicator for local business schema, such as $, $$, or $$$.', 'seo-and-social' ),
			)
		);
		sas_textarea_field(
			'sas_settings[seo][custom_schema_json]',
			__( 'Custom schema JSON', 'seo-and-social' ),
			$seo['custom_schema_json'],
			array(
				'rows' => 8,
				'info' => __( 'Optional advanced JSON merged into the schema payload by the frontend. Invalid JSON is deleted and not saved.', 'seo-and-social' ),
			)
		);
		?>
		<p class="sas-warning-note"><?php echo esc_html__( 'Invalid JSON is automatically deleted and will not be saved, to prevent breaking the API.', 'seo-and-social' ); ?></p>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Extra schema properties', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<p class="description"><?php echo esc_html__( 'Use structured rows for schema data that does not have a dedicated field.', 'seo-and-social' ); ?></p>
		<div class="sas-repeatable" data-sas-repeater="extra-schema-properties">
			<div class="sas-repeatable-rows" data-sas-rows>
				<?php foreach ( $seo['extra_schema_properties'] as $index => $row ) : ?>
					<?php sas_render_extra_schema_row( (string) $index, $row ); ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button" data-sas-add-row data-template="tmpl-sas-extra-schema-row">
				<?php echo esc_html__( 'Add row', 'seo-and-social' ); ?>
			</button>
		</div>
		<script type="text/template" id="tmpl-sas-extra-schema-row">
			<?php sas_render_extra_schema_row( '__INDEX__', array() ); ?>
		</script>
		</div>
	</details>
	<?php
}

/**
 * Render Settings tab.
 *
 * @param array $settings Settings.
 * @return void
 */
function sas_render_settings_tab( $settings ) {
	$behavior = $settings['settings'];
	$post_types = sas_get_available_post_types();
	?>
	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Plugin behavior', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<?php
		sas_checkbox_field( 'sas_settings[settings][enable_seo_meta_box]', __( 'Enable SEO meta box', 'seo-and-social' ), $behavior['enable_seo_meta_box'] );
		sas_checkbox_field( 'sas_settings[settings][enable_faq_meta_box]', __( 'Enable FAQ meta box', 'seo-and-social' ), $behavior['enable_faq_meta_box'] );
		sas_checkbox_field(
			'sas_settings[settings][enable_public_rest_endpoint]',
			__( 'Enable public REST endpoint', 'seo-and-social' ),
			$behavior['enable_public_rest_endpoint'],
			array(
				'info' => __( 'When enabled, the settings endpoint is public for headless frontends and protected by a lightweight unauthenticated rate limit. When disabled, the endpoint requires an authenticated administrator.', 'seo-and-social' ),
			)
		);
		sas_checkbox_field( 'sas_settings[settings][enable_content_rest_fields]', __( 'Enable REST fields on content items', 'seo-and-social' ), $behavior['enable_content_rest_fields'] );
		sas_checkbox_field( 'sas_settings[settings][faq_allow_html]', __( 'Allow basic HTML in FAQ answers', 'seo-and-social' ), $behavior['faq_allow_html'] );
		?>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'OG image optimization', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<?php
		sas_checkbox_field(
			'sas_settings[settings][enable_og_image_optimization]',
			__( 'OG image optimization', 'seo-and-social' ),
			$behavior['enable_og_image_optimization'],
			array(
				'info' => __( 'When enabled, OG images selected through Seo & Social get a separate 1200x630 WebP generated with cover crop. Originals are not changed and remain the API fallback if optimization fails. Transparent originals keep transparency where the server WebP encoder supports it.', 'seo-and-social' ),
			)
		);
		?>
		<p>
			<button type="submit" class="button" form="sas-regenerate-og-images-form">
				<?php echo esc_html__( 'Regenerate OG images', 'seo-and-social' ); ?>
			</button>
		</p>
		<p>
			<button type="submit" class="button button-link-delete" form="sas-delete-optimized-og-images-form" data-sas-confirm-delete-og-images>
				<?php echo esc_html__( 'Delete generated WebP images', 'seo-and-social' ); ?>
			</button>
		</p>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'SEO post types', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<?php sas_post_type_checkboxes( 'sas_settings[settings][seo_post_types][]', $post_types, $behavior['seo_post_types'] ); ?>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'FAQ post types', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<?php sas_post_type_checkboxes( 'sas_settings[settings][faq_post_types][]', $post_types, $behavior['faq_post_types'] ); ?>
		</div>
	</details>

	<details class="sas-panel" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'REST names', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<?php
		sas_text_field(
			'sas_settings[settings][rest_namespace]',
			__( 'REST namespace', 'seo-and-social' ),
			$behavior['rest_namespace'],
			array(
				'placeholder' => 'headless-seo/v1',
				'info' => __( 'Default endpoint namespace. Changing it changes the public settings URL.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[settings][settings_endpoint_path]',
			__( 'Main settings endpoint path', 'seo-and-social' ),
			$behavior['settings_endpoint_path'],
			array(
				'placeholder' => '/site-settings',
			)
		);
		sas_text_field(
			'sas_settings[settings][seo_rest_field_name]',
			__( 'SEO REST field name', 'seo-and-social' ),
			$behavior['seo_rest_field_name'],
			array(
				'placeholder' => 'seo_overrides',
				'info' => __( 'Field name for local per-content SEO overrides. The resolved final SEO payload is always exposed separately as seo_resolved.', 'seo-and-social' ),
			)
		);
		sas_text_field(
			'sas_settings[settings][faq_rest_field_name]',
			__( 'FAQ REST field name', 'seo-and-social' ),
			$behavior['faq_rest_field_name'],
			array(
				'placeholder' => 'faq_items',
			)
		);
		?>
		</div>
	</details>

	<details class="sas-panel sas-danger-zone" open>
		<summary class="sas-panel-summary"><?php echo esc_html__( 'Danger zone', 'seo-and-social' ); ?></summary>
		<div class="sas-panel-content">
		<p><?php echo esc_html__( 'This permanently deletes all Seo & Social settings, all SEO and FAQ post meta saved by this plugin, and all generated WebP OG images. Original media files are not deleted. This cannot be undone.', 'seo-and-social' ); ?></p>
		<button type="submit" class="button button-link-delete sas-delete-data-button" form="sas-delete-all-data-form" data-sas-confirm-delete>
			<?php echo esc_html__( 'Delete all plugin data', 'seo-and-social' ); ?>
		</button>
		</div>
	</details>
	<?php
}

/**
 * Handle settings save.
 *
 * @return void
 */
function sas_handle_save_settings() {
	if ( ! current_user_can( 'sas_access_plugin' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage these settings.', 'seo-and-social' ) );
	}

	check_admin_referer( 'sas_save_settings', 'sas_settings_nonce' );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The raw settings array is sanitized field-by-field by sas_sanitize_settings().
	$raw = isset( $_POST['sas_settings'] ) ? wp_unslash( $_POST['sas_settings'] ) : array();
	$active_tab = isset( $_POST['sas_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['sas_active_tab'] ) ) : 'social';

	if ( $active_tab === 'settings' && ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage plugin settings.', 'seo-and-social' ) );
	}

	if ( ! in_array( $active_tab, array( 'social', 'seo', 'llms', 'settings' ), true ) ) {
		$active_tab = 'social';
	}

	$had_invalid_json = $active_tab === 'seo' && sas_settings_payload_has_invalid_json( $raw );
	$settings = sas_sanitize_settings( $raw, sas_get_settings(), $active_tab );

	update_option( SAS_OPTION_NAME, $settings, false );

	if ( $active_tab === 'seo' && ! empty( $settings['seo']['default_og_image_id'] ) ) {
		sas_get_optimized_og_image( (int) $settings['seo']['default_og_image_id'], true );
	}

	$message = $had_invalid_json ? 'json_error' : 'saved';
	$redirect = add_query_arg(
		array(
			'page' => 'seo-and-social',
			'tab' => $active_tab,
			'sas_message' => $message,
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Delete all plugin data from the WordPress database.
 *
 * @return void
 */
function sas_handle_delete_all_data() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to delete this plugin data.', 'seo-and-social' ) );
	}

	check_admin_referer( 'sas_delete_all_data', 'sas_delete_all_data_nonce' );

	sas_delete_all_optimized_og_images();
	delete_option( SAS_OPTION_NAME );
	delete_post_meta_by_key( SAS_SEO_META_KEY );
	delete_post_meta_by_key( SAS_FAQ_META_KEY );

	$redirect = add_query_arg(
		array(
			'page' => 'seo-and-social',
			'tab' => 'settings',
			'sas_message' => 'deleted',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Regenerate optimized OG images for currently used image attachments.
 *
 * @return void
 */
function sas_handle_regenerate_og_images() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to regenerate OG images.', 'seo-and-social' ) );
	}

	check_admin_referer( 'sas_regenerate_og_images', 'sas_regenerate_og_images_nonce' );

	$result = sas_regenerate_all_og_images();
	$status = $result['failed'] > 0 ? 'warning' : 'success';
	$message = sprintf(
		/* translators: 1: generated count, 2: failed count, 3: skipped count. */
		__( 'OG images regenerated. Generated: %1$d. Failed: %2$d. Skipped: %3$d.', 'seo-and-social' ),
		$result['generated'],
		$result['failed'],
		$result['skipped']
	);

	$redirect = add_query_arg(
		array(
			'page' => 'seo-and-social',
			'tab' => 'settings',
			'sas_status' => $status,
			'sas_notice' => $message,
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Delete generated optimized OG images without deleting settings.
 *
 * @return void
 */
function sas_handle_delete_optimized_og_images() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to delete generated OG images.', 'seo-and-social' ) );
	}

	check_admin_referer( 'sas_delete_optimized_og_images', 'sas_delete_optimized_og_images_nonce' );

	$deleted = sas_delete_all_optimized_og_images();
	$message = sprintf(
		/* translators: %d: deleted image count. */
		__( 'Generated WebP OG images deleted: %d.', 'seo-and-social' ),
		$deleted
	);

	$redirect = add_query_arg(
		array(
			'page' => 'seo-and-social',
			'tab' => 'settings',
			'sas_status' => 'success',
			'sas_notice' => $message,
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Detect invalid JSON fields before sanitization removes them.
 *
 * @param mixed $raw Raw settings.
 * @return bool
 */
function sas_settings_payload_has_invalid_json( $raw ) {
	if ( ! is_array( $raw ) ) {
		return false;
	}

	$seo = $raw['seo'] ?? array();

	if ( ! empty( $seo['custom_schema_json'] ) && sas_sanitize_json_string( $seo['custom_schema_json'] ) === '' ) {
		return true;
	}

	foreach ( (array) ( $seo['extra_schema_properties'] ?? array() ) as $row ) {
		if ( ! is_array( $row ) || ( $row['type'] ?? '' ) !== 'json' ) {
			continue;
		}

		if ( ! empty( $row['value'] ) && sas_sanitize_json_string( $row['value'] ) === '' ) {
			return true;
		}
	}

	return false;
}

/**
 * Render text field.
 *
 * @param string $name Field name.
 * @param string $label Label.
 * @param string $value Value.
 * @param array  $args Args.
 * @return void
 */
function sas_text_field( $name, $label, $value, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'type' => 'text',
			'placeholder' => '',
			'info' => '',
			'media' => false,
			'media_id_target' => '',
		)
	);
	$id = sas_field_id( $name );
	?>
	<div class="sas-field">
		<div class="sas-label-row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php sas_info_button( $id, $args['info'] ); ?>
		</div>
		<div class="sas-input-row">
			<input
				type="<?php echo esc_attr( $args['type'] ); ?>"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
				class="regular-text"
				<?php if ( $args['media_id_target'] ) : ?>
					data-sas-media-linked-id="<?php echo esc_attr( $args['media_id_target'] ); ?>"
				<?php endif; ?>
			>
			<?php if ( $args['media'] ) : ?>
				<button
					type="button"
					class="button"
					data-sas-media-url-target="<?php echo esc_attr( $id ); ?>"
					<?php if ( $args['media_id_target'] ) : ?>
						data-sas-media-id-target="<?php echo esc_attr( $args['media_id_target'] ); ?>"
					<?php endif; ?>
				>
					<?php echo esc_html__( 'Choose image', 'seo-and-social' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php sas_info_panel( $id, $args['info'] ); ?>
	</div>
	<?php
}

/**
 * Render textarea field.
 *
 * @param string $name Field name.
 * @param string $label Label.
 * @param string $value Value.
 * @param array  $args Args.
 * @return void
 */
function sas_textarea_field( $name, $label, $value, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'rows' => 4,
			'info' => '',
		)
	);
	$id = sas_field_id( $name );
	?>
	<div class="sas-field">
		<div class="sas-label-row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php sas_info_button( $id, $args['info'] ); ?>
		</div>
		<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="<?php echo esc_attr( (string) $args['rows'] ); ?>" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
		<?php sas_info_panel( $id, $args['info'] ); ?>
	</div>
	<?php
}

/**
 * Render checkbox field.
 *
 * @param string $name Field name.
 * @param string $label Label.
 * @param bool   $checked Checked.
 * @param array  $args Args.
 * @return void
 */
function sas_checkbox_field( $name, $label, $checked, $args = array() ) {
	$args = wp_parse_args( $args, array( 'info' => '' ) );
	$id = sas_field_id( $name );
	?>
	<div class="sas-field sas-checkbox-field">
		<div class="sas-label-row">
			<label for="<?php echo esc_attr( $id ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php sas_info_button( $id, $args['info'] ); ?>
		</div>
		<?php sas_info_panel( $id, $args['info'] ); ?>
	</div>
	<?php
}

/**
 * Render post type checkboxes.
 *
 * @param string $name Field name.
 * @param array  $post_types Post type objects.
 * @param array  $selected Selected names.
 * @return void
 */
function sas_post_type_checkboxes( $name, $post_types, $selected ) {
	?>
	<div class="sas-check-grid">
		<?php foreach ( $post_types as $post_type => $object ) : ?>
			<?php $id = sas_field_id( $name . '_' . $post_type ); ?>
			<label for="<?php echo esc_attr( $id ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, (array) $selected, true ) ); ?>>
				<?php echo esc_html( $object->labels->singular_name ); ?>
				<code><?php echo esc_html( $post_type ); ?></code>
			</label>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Render extra social row.
 *
 * @param string $index Row index.
 * @param array  $row Row data.
 * @return void
 */
function sas_render_extra_social_row( $index, $row ) {
	$row = wp_parse_args(
		$row,
		array(
			'key' => '',
			'label' => '',
			'url' => '',
		)
	);
	?>
	<div class="sas-row">
		<input type="text" name="sas_settings[social][extra_links][<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $row['key'] ); ?>" placeholder="<?php echo esc_attr__( 'key', 'seo-and-social' ); ?>">
		<input type="text" name="sas_settings[social][extra_links][<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>" placeholder="<?php echo esc_attr__( 'Label', 'seo-and-social' ); ?>">
		<input type="url" name="sas_settings[social][extra_links][<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $row['url'] ); ?>" placeholder="https://">
		<button type="button" class="button-link-delete" data-sas-remove-row><?php echo esc_html__( 'Remove', 'seo-and-social' ); ?></button>
	</div>
	<?php
}

/**
 * Render extra schema row.
 *
 * @param string $index Row index.
 * @param array  $row Row data.
 * @return void
 */
function sas_render_extra_schema_row( $index, $row ) {
	$row = wp_parse_args(
		$row,
		array(
			'key' => '',
			'type' => 'text',
			'value' => '',
		)
	);
	$value = $row['value'];

	if ( is_array( $value ) ) {
		$value = implode( "\n", $value );
	}
	?>
	<div class="sas-row sas-schema-row">
		<input type="text" name="sas_settings[seo][extra_schema_properties][<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $row['key'] ); ?>" placeholder="<?php echo esc_attr__( 'propertyKey', 'seo-and-social' ); ?>">
		<select name="sas_settings[seo][extra_schema_properties][<?php echo esc_attr( $index ); ?>][type]">
			<?php foreach ( array( 'text', 'url', 'list', 'json' ) as $type ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $row['type'], $type ); ?>><?php echo esc_html( $type ); ?></option>
			<?php endforeach; ?>
		</select>
		<textarea name="sas_settings[seo][extra_schema_properties][<?php echo esc_attr( $index ); ?>][value]" rows="3" placeholder="<?php echo esc_attr__( 'Value', 'seo-and-social' ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		<button type="button" class="button-link-delete" data-sas-remove-row><?php echo esc_html__( 'Remove', 'seo-and-social' ); ?></button>
	</div>
	<?php
}

/**
 * Render LLMs.txt recommended page row.
 *
 * @param string $index Row index.
 * @param array  $row Row data.
 * @return void
 */
function sas_render_llms_recommended_page_row( $index, $row ) {
	$row = wp_parse_args(
		$row,
		array(
			'label' => '',
			'url' => '',
			'note' => '',
		)
	);
	?>
	<div class="sas-row sas-llms-page-row">
		<input type="text" name="sas_settings[llms][recommended_pages][<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>" placeholder="<?php echo esc_attr__( 'Label', 'seo-and-social' ); ?>">
		<input type="url" name="sas_settings[llms][recommended_pages][<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $row['url'] ); ?>" placeholder="https://">
		<textarea name="sas_settings[llms][recommended_pages][<?php echo esc_attr( $index ); ?>][note]" rows="2" placeholder="<?php echo esc_attr__( 'Optional note', 'seo-and-social' ); ?>"><?php echo esc_textarea( $row['note'] ); ?></textarea>
		<button type="button" class="button-link-delete" data-sas-remove-row><?php echo esc_html__( 'Remove', 'seo-and-social' ); ?></button>
	</div>
	<?php
}

/**
 * Render LLMs.txt ignored section row.
 *
 * @param string $index Row index.
 * @param array  $row Row data.
 * @return void
 */
function sas_render_llms_ignored_section_row( $index, $row ) {
	$row = wp_parse_args(
		$row,
		array(
			'label' => '',
			'note' => '',
		)
	);
	?>
	<div class="sas-row sas-llms-ignored-row">
		<input type="text" name="sas_settings[llms][ignored_sections][<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>" placeholder="<?php echo esc_attr__( 'Section label', 'seo-and-social' ); ?>">
		<textarea name="sas_settings[llms][ignored_sections][<?php echo esc_attr( $index ); ?>][note]" rows="2" placeholder="<?php echo esc_attr__( 'Reason or description', 'seo-and-social' ); ?>"><?php echo esc_textarea( $row['note'] ); ?></textarea>
		<button type="button" class="button-link-delete" data-sas-remove-row><?php echo esc_html__( 'Remove', 'seo-and-social' ); ?></button>
	</div>
	<?php
}

/**
 * Render info button.
 *
 * @param string $id Field id.
 * @param string $info Info text.
 * @return void
 */
function sas_info_button( $id, $info ) {
	if ( ! $info ) {
		return;
	}
	?>
	<button
		type="button"
		class="sas-info-button"
		aria-expanded="false"
		aria-controls="<?php echo esc_attr( $id . '_info' ); ?>"
		title="<?php echo esc_attr__( 'Field information', 'seo-and-social' ); ?>"
	>?</button>
	<?php
}

/**
 * Render info panel.
 *
 * @param string $id Field id.
 * @param string $info Info text.
 * @return void
 */
function sas_info_panel( $id, $info ) {
	if ( ! $info ) {
		return;
	}
	?>
	<p id="<?php echo esc_attr( $id . '_info' ); ?>" class="sas-info-panel" hidden><?php echo esc_html( $info ); ?></p>
	<?php
}

/**
 * Build stable field id from a field name.
 *
 * @param string $name Field name.
 * @return string
 */
function sas_field_id( $name ) {
	return 'sas_' . sanitize_key( str_replace( array( '[', ']' ), '_', $name ) );
}
