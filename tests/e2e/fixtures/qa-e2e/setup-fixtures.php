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

$seo_fixture = get_page_by_path( 'sas-e2e-seo', OBJECT, 'page' );
$seo_post_id = wp_insert_post(
	array(
		'ID'          => $seo_fixture ? $seo_fixture->ID : 0,
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => 'Seo & Social E2E SEO Fixture',
		'post_name'   => 'sas-e2e-seo',
		'post_content' => 'Deterministic content used by the Playwright SEO meta-box suite.',
	),
	true
);

if ( is_wp_error( $seo_post_id ) ) {
	WP_CLI::error( $seo_post_id->get_error_message() );
}

delete_post_meta( $seo_post_id, '_sas_seo_overrides' );

$faq_fixture = get_page_by_path( 'sas-e2e-faq', OBJECT, 'page' );
$faq_post_id = wp_insert_post(
	array(
		'ID'           => $faq_fixture ? $faq_fixture->ID : 0,
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Seo & Social E2E FAQ Fixture',
		'post_name'    => 'sas-e2e-faq',
		'post_content' => 'Deterministic content used by the Playwright FAQ meta-box suite.',
	),
	true
);

if ( is_wp_error( $faq_post_id ) ) {
	WP_CLI::error( $faq_post_id->get_error_message() );
}

delete_post_meta( $faq_post_id, '_sas_faq_items' );

WP_CLI::success( 'Seo & Social E2E fixtures are ready.' );
