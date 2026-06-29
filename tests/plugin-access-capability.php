<?php
/**
 * Regression test for plugin admin access roles.
 *
 * @package SeoAndSocial
 */

define('ABSPATH', __DIR__);

class Sas_Test_User {
    public $roles;
    public $allcaps;

    public function __construct($roles, $allcaps = []) {
        $this->roles = $roles;
        $this->allcaps = $allcaps;
    }
}

$GLOBALS['sas_test_users'] = [
    1 => new Sas_Test_User(['administrator']),
    2 => new Sas_Test_User(['editor']),
    3 => new Sas_Test_User(['author']),
    4 => new Sas_Test_User(['contributor']),
    5 => new Sas_Test_User(['custom_seo_manager'], ['manage_seo_social' => true]),
];

$GLOBALS['sas_test_filters'] = [];

function get_userdata($user_id) {
    return $GLOBALS['sas_test_users'][$user_id] ?? false;
}

function apply_filters($hook, $value) {
    return $GLOBALS['sas_test_filters'][$hook] ?? $value;
}

function sas_map_plugin_access_capability($caps, $cap, $user_id, $args) {
    if ($cap !== 'sas_access_plugin') {
        return $caps;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return ['do_not_allow'];
    }

    $roles = (array) $user->roles;
    $allowed_roles = (array) apply_filters('sas_plugin_access_roles', ['administrator']);
    $allowed_capabilities = array_diff((array) apply_filters('sas_plugin_access_capabilities', []), ['sas_access_plugin']);

    if (array_intersect($roles, $allowed_roles)) {
        return ['read'];
    }

    foreach ($allowed_capabilities as $allowed_capability) {
        if (!empty($user->allcaps[$allowed_capability])) {
            return ['read'];
        }
    }

    return ['do_not_allow'];
}

assert(sas_map_plugin_access_capability([], 'sas_access_plugin', 1, []) === ['read']);
assert(sas_map_plugin_access_capability([], 'sas_access_plugin', 2, []) === ['do_not_allow']);
assert(sas_map_plugin_access_capability([], 'sas_access_plugin', 3, []) === ['do_not_allow']);
assert(sas_map_plugin_access_capability([], 'sas_access_plugin', 4, []) === ['do_not_allow']);
assert(sas_map_plugin_access_capability(['read'], 'read', 3, []) === ['read']);

$GLOBALS['sas_test_filters']['sas_plugin_access_roles'] = ['administrator', 'editor', 'custom_seo_manager'];
assert(sas_map_plugin_access_capability([], 'sas_access_plugin', 2, []) === ['read']);
assert(sas_map_plugin_access_capability([], 'sas_access_plugin', 5, []) === ['read']);

$GLOBALS['sas_test_filters']['sas_plugin_access_roles'] = ['administrator'];
$GLOBALS['sas_test_filters']['sas_plugin_access_capabilities'] = ['manage_seo_social'];
assert(sas_map_plugin_access_capability([], 'sas_access_plugin', 5, []) === ['read']);

echo "Plugin access capability test passed.\n";
