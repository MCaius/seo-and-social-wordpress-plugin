<?php
/**
 * Bootstrap the WordPress integration test suite.
 *
 * @package SeoAndSocial
 */

$sas_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $sas_tests_dir ) {
	$sas_tests_dir = '/wordpress-phpunit';
}

$sas_plugin_dir = dirname( __DIR__ );
$sas_vendor_dir = $sas_plugin_dir . '/vendor';

if ( ! file_exists( $sas_vendor_dir . '/autoload.php' ) ) {
	echo "Composer dependencies are missing from the PHPUnit environment.\n";
	exit( 1 );
}

require_once $sas_vendor_dir . '/autoload.php';

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $sas_vendor_dir . '/yoast/phpunit-polyfills' );
}

require_once $sas_tests_dir . '/includes/functions.php';

/**
 * Load the plugin before WordPress finishes bootstrapping the test site.
 *
 * @return void
 */
function sas_tests_load_plugin() {
	require dirname( __DIR__ ) . '/seo-and-social.php';
}

tests_add_filter( 'muplugins_loaded', 'sas_tests_load_plugin' );

require $sas_tests_dir . '/includes/bootstrap.php';
