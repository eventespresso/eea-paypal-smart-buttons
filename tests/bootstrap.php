<?php
/**
 * Bootstrap for eea-paypal-smart-buttons tests
 */

use EETests\bootstrap\AddonLoader;

$core_tests_dir = dirname(dirname(dirname(__FILE__))) . '/event-espresso-core/tests/';
//if still don't have $core_tests_dir, then let's check tmp folder.
if (! is_dir($core_tests_dir)) {
    $core_tests_dir = '/tmp/event-espresso-core/tests/';
}
require $core_tests_dir . 'includes/CoreLoader.php';
require $core_tests_dir . 'includes/AddonLoader.php';

define('EEA_PP_SMART_BUTTONS_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/');
define('EEA_PP_SMART_BUTTONS_TESTS_DIR', EEA_PP_SMART_BUTTONS_PLUGIN_DIR . 'tests/');

$addon_loader = new AddonLoader(
    EEA_PP_SMART_BUTTONS_TESTS_DIR,
    EEA_PP_SMART_BUTTONS_PLUGIN_DIR,
    'eea-paypal-smart-buttons.php'
);
$addon_loader->init();