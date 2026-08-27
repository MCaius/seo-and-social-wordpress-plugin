<?php
/**
 * GitHub Releases update integration.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAS_UPDATE_URI', 'https://github.com/MCaius/seo-and-social-wordpress-plugin' );
define( 'SAS_GITHUB_RELEASES_API', 'https://api.github.com/repos/MCaius/seo-and-social-wordpress-plugin/releases/latest' );
define( 'SAS_GITHUB_RELEASE_ASSET', 'seo-and-social.zip' );
define( 'SAS_GITHUB_RELEASE_CACHE_KEY', 'sas_github_release_update' );

/**
 * Parse and validate a GitHub release response.
 *
 * @param mixed $payload Decoded GitHub API response.
 * @return array|false Validated release data or false.
 */
function sas_parse_github_release( $payload ) {
	if ( ! is_array( $payload ) || ! empty( $payload['draft'] ) || ! empty( $payload['prerelease'] ) ) {
		return false;
	}

	$tag = isset( $payload['tag_name'] ) ? trim( (string) $payload['tag_name'] ) : '';

	if ( ! preg_match( '/^v?([0-9]+(?:\.[0-9]+){1,3})$/', $tag, $matches ) ) {
		return false;
	}

	$version = $matches[1];
	$package = '';

	foreach ( (array) ( $payload['assets'] ?? array() ) as $asset ) {
		if ( ! is_array( $asset ) || ( $asset['name'] ?? '' ) !== SAS_GITHUB_RELEASE_ASSET ) {
			continue;
		}

		if ( isset( $asset['state'] ) && $asset['state'] !== 'uploaded' ) {
			continue;
		}

		$asset_url = isset( $asset['browser_download_url'] ) ? esc_url_raw( $asset['browser_download_url'], array( 'https' ) ) : '';
		$parts = $asset_url ? wp_parse_url( $asset_url ) : false;
		$expected_path = '/MCaius/seo-and-social-wordpress-plugin/releases/download/';

		if (
			! is_array( $parts ) ||
			( $parts['scheme'] ?? '' ) !== 'https' ||
			strtolower( (string) ( $parts['host'] ?? '' ) ) !== 'github.com' ||
			strpos( (string) ( $parts['path'] ?? '' ), $expected_path ) !== 0
		) {
			continue;
		}

		$package = $asset_url;
		break;
	}

	if ( $package === '' ) {
		return false;
	}

	$release_url = isset( $payload['html_url'] ) ? esc_url_raw( $payload['html_url'], array( 'https' ) ) : '';
	$release_parts = $release_url ? wp_parse_url( $release_url ) : false;

	if (
		! is_array( $release_parts ) ||
		( $release_parts['scheme'] ?? '' ) !== 'https' ||
		strtolower( (string) ( $release_parts['host'] ?? '' ) ) !== 'github.com' ||
		strpos( (string) ( $release_parts['path'] ?? '' ), '/MCaius/seo-and-social-wordpress-plugin/releases/' ) !== 0
	) {
		$release_url = SAS_UPDATE_URI;
	}

	return array(
		'version' => $version,
		'url' => $release_url,
		'package' => $package,
	);
}

/**
 * Fetch the latest validated GitHub release, with a site-wide cache.
 *
 * @return array|false Validated release data or false.
 */
function sas_get_latest_github_release() {
	$cached = get_site_transient( SAS_GITHUB_RELEASE_CACHE_KEY );

	if ( is_array( $cached ) && array_key_exists( 'release', $cached ) ) {
		return is_array( $cached['release'] ) ? $cached['release'] : false;
	}

	$response = wp_remote_get(
		SAS_GITHUB_RELEASES_API,
		array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/vnd.github+json',
				'User-Agent' => 'Seo-and-Social-WordPress-Plugin/' . SAS_VERSION,
			),
		)
	);

	$release = false;

	if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
		$payload = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( json_last_error() === JSON_ERROR_NONE ) {
			$release = sas_parse_github_release( $payload );
		}
	}

	set_site_transient(
		SAS_GITHUB_RELEASE_CACHE_KEY,
		array( 'release' => $release ),
		$release ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS
	);

	return $release;
}

/**
 * Clear the GitHub release cache when WordPress forces a fresh plugin check.
 *
 * @return void
 */
function sas_clear_github_release_cache() {
	delete_site_transient( SAS_GITHUB_RELEASE_CACHE_KEY );
}

/**
 * Supply update metadata for this plugin through WordPress's Update URI hook.
 *
 * @param array|false $update Current third-party update data.
 * @param array       $plugin_data Plugin header data.
 * @param string      $plugin_file Plugin basename.
 * @param array       $locales Installed locales.
 * @return array|false Update data or false.
 */
function sas_filter_github_plugin_update( $update, $plugin_data, $plugin_file, $locales ) {
	unset( $locales );

	if (
		$plugin_file !== plugin_basename( SAS_PLUGIN_FILE ) ||
		( $plugin_data['UpdateURI'] ?? '' ) !== SAS_UPDATE_URI
	) {
		return $update;
	}

	$release = sas_get_latest_github_release();
	$current_version = isset( $plugin_data['Version'] ) ? (string) $plugin_data['Version'] : SAS_VERSION;

	if ( ! $release || ! version_compare( $release['version'], $current_version, '>' ) ) {
		return false;
	}

	return array(
		'id' => SAS_UPDATE_URI,
		'version' => $release['version'],
		'url' => $release['url'],
		'package' => $release['package'],
		'requires_php' => '8.0',
		'autoupdate' => false,
	);
}
