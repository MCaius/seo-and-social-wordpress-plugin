<?php
/**
 * Create deterministic, disposable users for the Playwright environment.
 */

$users = array(
	'sas-editor'      => array( 'role' => 'editor', 'email' => 'sas-editor@example.test' ),
	'sas-author'      => array( 'role' => 'author', 'email' => 'sas-author@example.test' ),
	'sas-contributor' => array( 'role' => 'contributor', 'email' => 'sas-contributor@example.test' ),
	'sas-subscriber'  => array( 'role' => 'subscriber', 'email' => 'sas-subscriber@example.test' ),
	'sas-custom'      => array( 'role' => 'sas_qa_custom', 'email' => 'sas-custom@example.test' ),
);

add_role( 'sas_qa_custom', 'Seo & Social QA', array( 'read' => true ) );

foreach ( $users as $login => $fixture ) {
	$user = get_user_by( 'login', $login );

	if ( ! $user ) {
		$user_id = wp_insert_user(
			array(
				'user_login' => $login,
				'user_pass'  => 'password',
				'user_email' => $fixture['email'],
				'role'       => $fixture['role'],
			)
		);

		if ( is_wp_error( $user_id ) ) {
			WP_CLI::error( $user_id->get_error_message() );
		}

		continue;
	}

	wp_set_password( 'password', $user->ID );
	$user->set_role( $fixture['role'] );
}

WP_CLI::success( 'Seo & Social E2E fixtures are ready.' );
