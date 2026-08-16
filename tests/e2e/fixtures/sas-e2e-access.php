<?php
/**
 * E2E-only access customization loaded as a must-use plugin by wp-env.
 */

add_filter(
	'sas_plugin_access_roles',
	static function ( $roles ) {
		$roles[] = 'sas_qa_custom';

		return array_values( array_unique( $roles ) );
	}
);
