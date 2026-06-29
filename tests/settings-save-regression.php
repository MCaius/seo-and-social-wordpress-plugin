<?php
/**
 * Regression test for tabbed settings saves.
 *
 * @package SeoAndSocial
 */

define('ABSPATH', __DIR__);
define('SAS_OPTION_NAME', 'sas_settings');

function sanitize_key($key) {
    return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $key));
}

function sanitize_text_field($value) {
    return trim(strip_tags((string) $value));
}

function sanitize_textarea_field($value) {
    return trim(strip_tags((string) $value));
}

function sanitize_email($value) {
    return filter_var((string) $value, FILTER_SANITIZE_EMAIL);
}

function sanitize_title($value) {
    return trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $value)), '-');
}

function esc_url_raw($value) {
    $value = trim((string) $value);

    return preg_match('#^https?://#', $value) ? $value : '';
}

function wp_json_encode($value, $flags = 0) {
    return json_encode($value, $flags);
}

function get_post_types($args, $output = 'names') {
    $names = ['post', 'page', 'project', 'attachment'];

    if ($output === 'objects') {
        $objects = [];

        foreach ($names as $name) {
            $object = new stdClass();
            $object->labels = new stdClass();
            $object->labels->singular_name = ucfirst($name);
            $objects[$name] = $object;
        }

        return $objects;
    }

    return $names;
}

function get_post_type_object($post_type) {
    return null;
}

require_once __DIR__ . '/../seo-and-social/includes/helpers/settings.php';
require_once __DIR__ . '/../seo-and-social/includes/helpers/sanitization.php';

$existing = sas_get_default_settings();
$existing['settings']['enable_seo_meta_box'] = true;
$existing['settings']['enable_faq_meta_box'] = true;
$existing['settings']['enable_public_rest_endpoint'] = true;
$existing['settings']['enable_content_rest_fields'] = true;
$existing['settings']['seo_post_types'] = ['post', 'page', 'project'];
$existing['settings']['faq_post_types'] = ['post', 'page', 'project'];
$existing['social']['email'] = 'old@example.com';

$after_social_save = sas_sanitize_settings(
    [
        'social' => [
            'email' => 'new@example.com',
            'phone_whatsapp' => '+1234567890',
            'facebook_url' => 'https://facebook.com/example',
            'instagram_url' => '',
            'tiktok_url' => '',
            'youtube_url' => '',
            'extra_links' => [],
        ],
    ],
    $existing,
    'social'
);

assert($after_social_save['social']['email'] === 'new@example.com');
assert($after_social_save['settings']['enable_seo_meta_box'] === true);
assert($after_social_save['settings']['enable_faq_meta_box'] === true);
assert($after_social_save['settings']['enable_public_rest_endpoint'] === true);
assert($after_social_save['settings']['enable_content_rest_fields'] === true);
assert($after_social_save['settings']['seo_post_types'] === ['post', 'page', 'project']);
assert($after_social_save['settings']['faq_post_types'] === ['post', 'page', 'project']);

$after_settings_save = sas_sanitize_settings(
    [
        'settings' => [
            'enable_seo_meta_box' => '1',
            'enable_faq_meta_box' => '1',
            'enable_public_rest_endpoint' => '1',
            'enable_content_rest_fields' => '1',
            'faq_allow_html' => '1',
            'seo_post_types' => ['post', 'page', 'project'],
            'faq_post_types' => ['post', 'page', 'project'],
            'rest_namespace' => 'headless-seo/v1',
            'settings_endpoint_path' => '/site-settings',
            'seo_rest_field_name' => 'seo_overrides',
            'faq_rest_field_name' => 'faq_items',
        ],
    ],
    $after_social_save,
    'settings'
);

assert($after_settings_save['social']['email'] === 'new@example.com');
assert($after_settings_save['social']['phone_whatsapp'] === '+1234567890');
assert($after_settings_save['settings']['seo_post_types'] === ['post', 'page', 'project']);
assert($after_settings_save['settings']['faq_post_types'] === ['post', 'page', 'project']);

echo "Settings save regression test passed.\n";
