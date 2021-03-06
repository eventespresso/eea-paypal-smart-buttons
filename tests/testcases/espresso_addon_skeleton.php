<?php
/**
 * Contains test class for eea-paypal-smart-buttons.php
 *
 * @since  		1.0.0
 * @package 	EE WPUsers
 * @subpackage 	Tests
 */
class eea_paypal_smart_buttons_tests extends EE_UnitTestCase {

	/**
	 * Tests the loading of the main file
	 *
	 * @since 0.0.1.dev.002
	 */
	function test_loading_paypal_smart_buttons() {
		$this->assertEquals( has_action('AHEE__EE_System__load_espresso_addons', 'load_espresso_paypal_smart_buttons'), 10 );
		$this->assertTrue( class_exists( 'EE_Paypal_Smart_Buttons' ) );
	}
}
