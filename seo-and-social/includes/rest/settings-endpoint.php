<?php
/**
 * Public settings REST endpoint.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register settings endpoint.
 *
 * @return void
 */
function sas_register_settings_endpoint() {
	$settings = sas_get_settings();

	register_rest_route(
		$settings['settings']['rest_namespace'],
		$settings['settings']['settings_endpoint_path'],
		array(
			'methods' => WP_REST_Server::READABLE,
			'permission_callback' => 'sas_settings_endpoint_permission',
			'callback' => static function () {
				return rest_ensure_response( sas_get_public_settings() );
			},
		)
	);

	register_rest_route(
		$settings['settings']['rest_namespace'],
		'/llms',
		array(
			'methods' => WP_REST_Server::READABLE,
			'permission_callback' => 'sas_settings_endpoint_permission',
			'callback' => static function () {
				return rest_ensure_response( sas_get_public_llms() );
			},
		)
	);
}

/**
 * Allow the plugin settings endpoint through global REST restrictions when public mode is enabled.
 *
 * @param WP_Error|null|bool $result Authentication result.
 * @return WP_Error|null|bool
 */
function sas_allow_public_settings_endpoint( $result ) {
	if ( ! sas_is_public_endpoint_request() ) {
		return $result;
	}

	$settings = sas_get_settings();

	if ( empty( $settings['settings']['enable_public_rest_endpoint'] ) ) {
		return $result;
	}

	return null;
}

/**
 * Check permissions for the settings endpoint.
 *
 * @return true|WP_Error
 */
function sas_settings_endpoint_permission() {
	$settings = sas_get_settings();

	if ( empty( $settings['settings']['enable_public_rest_endpoint'] ) ) {
		return current_user_can( 'manage_options' )
			? true
			: new WP_Error(
				'rest_forbidden',
				__( 'This endpoint requires authentication.', 'seo-and-social' ),
				array( 'status' => rest_authorization_required_code() )
			);
	}

	if ( is_user_logged_in() ) {
		return true;
	}

	return sas_check_public_settings_rate_limit();
}

/**
 * Check whether the current REST request is the plugin settings endpoint.
 *
 * @return bool
 */
function sas_is_settings_endpoint_request() {
	return sas_get_current_rest_route() === sas_get_settings_endpoint_route();
}

/**
 * Check whether the current REST request is one of the plugin public endpoints.
 *
 * @return bool
 */
function sas_is_public_endpoint_request() {
	$route = sas_get_current_rest_route();

	return in_array( $route, array( sas_get_settings_endpoint_route(), sas_get_llms_endpoint_route() ), true );
}

/**
 * Get configured settings endpoint route.
 *
 * @return string
 */
function sas_get_settings_endpoint_route() {
	$settings = sas_get_settings();

	return '/' . trim( $settings['settings']['rest_namespace'], '/' ) . '/' . trim( $settings['settings']['settings_endpoint_path'], '/' );
}

/**
 * Get configured LLMs endpoint route.
 *
 * @return string
 */
function sas_get_llms_endpoint_route() {
	$settings = sas_get_settings();

	return '/' . trim( $settings['settings']['rest_namespace'], '/' ) . '/llms';
}

/**
 * Get current REST route.
 *
 * @return string
 */
function sas_get_current_rest_route() {
	if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
		return '';
	}

	$route = '';

	if ( isset( $_GET['rest_route'] ) ) {
		$route = sanitize_text_field( wp_unslash( $_GET['rest_route'] ) );
	} elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$request_path = parse_url( $request_uri, PHP_URL_PATH );
		$rest_prefix = '/' . trim( rest_get_url_prefix(), '/' ) . '/';
		$prefix_position = strpos( (string) $request_path, $rest_prefix );

		if ( $prefix_position !== false ) {
			$route = '/' . substr( (string) $request_path, $prefix_position + strlen( $rest_prefix ) );
		}
	}

	return '/' . trim( $route, '/' );
}

/**
 * Apply a small unauthenticated rate limit to the public settings endpoint.
 *
 * @return true|WP_Error
 */
function sas_check_public_settings_rate_limit() {
	$limit = (int) apply_filters( 'sas_public_settings_rate_limit', 300 );
	$window = (int) apply_filters( 'sas_public_settings_rate_limit_window', 60 );

	if ( $limit <= 0 || $window <= 0 ) {
		return true;
	}

	$ip = sas_get_request_ip();
	$key = 'sas_rate_' . md5( $ip . '|' . gmdate( 'YmdHi', time() - ( time() % $window ) ) );
	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return new WP_Error(
			'sas_rate_limited',
			__( 'Too many requests. Please try again shortly.', 'seo-and-social' ),
			array( 'status' => 429 )
		);
	}

	set_transient( $key, $count + 1, $window + 5 );

	return true;
}

/**
 * Get request IP for lightweight rate limiting.
 *
 * @return string
 */
function sas_get_request_ip() {
	$headers = (array) apply_filters( 'sas_trusted_proxy_ip_headers', array() );
	$headers[] = 'REMOTE_ADDR';
	$headers = array_values(
		array_unique(
			array_filter(
				array_map( 'sanitize_key', $headers )
			)
		)
	);

	$server_headers = array(
		'cf_connecting_ip' => 'HTTP_CF_CONNECTING_IP',
		'x_forwarded_for' => 'HTTP_X_FORWARDED_FOR',
		'remote_addr' => 'REMOTE_ADDR',
		'http_cf_connecting_ip' => 'HTTP_CF_CONNECTING_IP',
		'http_x_forwarded_for' => 'HTTP_X_FORWARDED_FOR',
		'REMOTE_ADDR',
	);

	foreach ( $headers as $header ) {
		$server_key = $server_headers[ $header ] ?? strtoupper( $header );

		if ( empty( $_SERVER[ $server_key ] ) ) {
			continue;
		}

		$value = sanitize_text_field( wp_unslash( $_SERVER[ $server_key ] ) );
		$ip = trim( explode( ',', $value )[0] );

		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	return 'unknown';
}
