<?php
/**
 * Regression test for settings endpoint permissions.
 *
 * @package SeoAndSocial
 */

define('ABSPATH', __DIR__);
define('REST_REQUEST', true);
define('SAS_OPTION_NAME', 'sas_settings');
define('SAS_TEXT_DOMAIN', 'seo-and-social');

class WP_Error {
    public $code;
    public $message;
    public $data;

    public function __construct($code, $message, $data = []) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
}

$GLOBALS['sas_test_logged_in'] = false;
$GLOBALS['sas_test_can_manage'] = false;
$GLOBALS['sas_test_options'] = [];
$GLOBALS['sas_test_transients'] = [];

function __($value, $domain = null) {
    return $value;
}

function apply_filters($hook, $value) {
    if ($hook === 'sas_public_settings_rate_limit') {
        return 2;
    }

    if ($hook === 'sas_public_settings_rate_limit_window') {
        return 60;
    }

    return $value;
}

function sanitize_key($key) {
    return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $key));
}

function sanitize_text_field($value) {
    return trim(strip_tags((string) $value));
}

function wp_unslash($value) {
    return $value;
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

function is_user_logged_in() {
    return $GLOBALS['sas_test_logged_in'];
}

function rest_authorization_required_code() {
    return 401;
}

function rest_get_url_prefix() {
    return 'wp-json';
}

function get_transient($key) {
    return $GLOBALS['sas_test_transients'][$key] ?? false;
}

function set_transient($key, $value, $expiration) {
    $GLOBALS['sas_test_transients'][$key] = $value;
}

require_once __DIR__ . '/../seo-and-social/includes/helpers/settings.php';
require_once __DIR__ . '/../seo-and-social/includes/rest/settings-endpoint.php';

$settings = sas_get_default_settings();
$settings['settings']['enable_public_rest_endpoint'] = true;
$GLOBALS['sas_test_options'][SAS_OPTION_NAME] = $settings;
$_SERVER['REQUEST_URI'] = '/wp-json/headless-seo/v1/site-settings';
$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

assert(sas_is_settings_endpoint_request() === true);
assert(sas_allow_public_settings_endpoint(new WP_Error('rest_forbidden', 'blocked')) === null);
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1';
assert(sas_settings_endpoint_permission() === true);
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.2';
assert(sas_settings_endpoint_permission() === true);
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.3';
assert(sas_settings_endpoint_permission() instanceof WP_Error);

$settings['settings']['enable_public_rest_endpoint'] = false;
$GLOBALS['sas_test_options'][SAS_OPTION_NAME] = $settings;
$GLOBALS['sas_test_can_manage'] = false;
assert(sas_allow_public_settings_endpoint(new WP_Error('rest_forbidden', 'blocked')) instanceof WP_Error);
assert(sas_settings_endpoint_permission() instanceof WP_Error);

$GLOBALS['sas_test_can_manage'] = true;
assert(sas_settings_endpoint_permission() === true);

echo "Settings endpoint permission test passed.\n";
