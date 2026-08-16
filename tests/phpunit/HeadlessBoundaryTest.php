<?php
/**
 * Headless-boundary regression coverage.
 *
 * @package SeoAndSocial
 */

require_once __DIR__ . '/TestCase.php';

class Seo_And_Social_Headless_Boundary_Test extends Seo_And_Social_Test_Case {
	/**
	 * Plugin callbacks must not render metadata or FAQ UI on frontend hooks.
	 *
	 * @return void
	 */
	public function test_plugin_does_not_register_frontend_render_callbacks() {
		global $wp_filter;

		foreach ( array( 'wp_head', 'wp_body_open', 'wp_footer' ) as $hook_name ) {
			if ( empty( $wp_filter[ $hook_name ] ) || empty( $wp_filter[ $hook_name ]->callbacks ) ) {
				continue;
			}

			foreach ( $wp_filter[ $hook_name ]->callbacks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'];
					$name = '';

					if ( is_string( $function ) ) {
						$name = $function;
					} elseif ( is_array( $function ) && isset( $function[1] ) ) {
						$name = (string) $function[1];
					}

					$this->assertFalse(
						strpos( $name, 'sas_' ) === 0,
						'Seo & Social registered frontend callback ' . $name . ' on ' . $hook_name . '.'
					);
				}
			}
		}

		$this->assertTrue( true );
	}
}
