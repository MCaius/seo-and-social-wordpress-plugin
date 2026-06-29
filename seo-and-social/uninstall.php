<?php
/**
 * Uninstall handler.
 *
 * Seo & Social intentionally does not delete data automatically on uninstall.
 * Administrators can use the manual "Delete all plugin data" action before
 * uninstalling when they intentionally want to remove plugin data.
 *
 * @package SeoAndSocial
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Intentionally left blank. Do not delete saved data automatically.
