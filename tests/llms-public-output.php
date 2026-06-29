<?php
/**
 * Regression test for disabled LLMs.txt public output.
 *
 * @package SeoAndSocial
 */

define('ABSPATH', __DIR__);
define('SAS_OPTION_NAME', 'sas_settings');

$GLOBALS['sas_test_can_manage'] = false;
$GLOBALS['sas_test_options'] = [];

function sanitize_key($key) {
    return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $key));
}

function sanitize_text_field($value) {
    return trim(strip_tags((string) $value));
}

function sanitize_textarea_field($value) {
    return trim(strip_tags((string) $value));
}

function get_post_types($args, $output = 'names') {
    return ['post', 'page'];
}

function get_post_type_object($post_type) {
    return null;
}

function get_option($name, $default = false) {
    return $GLOBALS['sas_test_options'][$name] ?? $default;
}

function current_user_can($capability) {
    return $capability === 'manage_options' && $GLOBALS['sas_test_can_manage'];
}

function get_bloginfo($field) {
    return 'Example Site';
}

require_once __DIR__ . '/../seo-and-social/includes/helpers/settings.php';
require_once __DIR__ . '/../seo-and-social/includes/helpers/sanitization.php';

$settings = sas_get_default_settings();
$settings['llms'] = [
    'enabled' => false,
    'site_summary' => 'Draft private summary',
    'recommended_pages' => [
        [
            'label' => 'Private page',
            'url' => 'https://example.com/private',
            'note' => 'Draft note',
        ],
    ],
    'ignored_sections' => [],
    'custom_content' => 'Private draft instructions',
];
$GLOBALS['sas_test_options'][SAS_OPTION_NAME] = $settings;

$public = sas_get_public_llms();
assert($public === ['enabled' => false]);

$GLOBALS['sas_test_can_manage'] = true;
$admin = sas_get_public_llms();
assert($admin['enabled'] === false);
assert($admin['site_summary'] === 'Draft private summary');
assert(strpos($admin['rendered_txt'], 'Draft private summary') !== false);

echo "LLMs public output test passed.\n";
