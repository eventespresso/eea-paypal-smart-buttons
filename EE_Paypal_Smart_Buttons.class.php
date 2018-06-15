<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit(); }

// define the plugin directory path and URL
define( 'EE_PAYPAL_SMART_BUTTONS_BASENAME', plugin_basename( EE_PAYPAL_SMART_BUTTONS_PLUGIN_FILE ));
define( 'EE_PAYPAL_SMART_BUTTONS_PATH', plugin_dir_path( __FILE__ ));
define( 'EE_PAYPAL_SMART_BUTTONS_URL', plugin_dir_url( __FILE__ ));

/**
 * ------------------------------------------------------------------------
 *
 * Class  EE_Paypal_Smart_Buttons
 *
 * @package			Event Espresso
 * @subpackage		espresso-paypal-smart-buttons
 * @author			    Brent Christensen
 *
 *
 * ------------------------------------------------------------------------
 */
Class  EE_Paypal_Smart_Buttons extends EE_Addon {

	/**
	 * class constructor
	 */
	public function __construct() {
	}

	public static function register_addon() {
		// register addon via Plugin API
		EE_Register_Addon::register(
			'Paypal_Smart_Buttons',
			array(
				'version' 					=> EE_PAYPAL_SMART_BUTTONS_VERSION,
				'min_core_version' => '4.9.63.dev.000',
				'main_file_path' 				=> EE_PAYPAL_SMART_BUTTONS_PLUGIN_FILE,
                'namespace'             => array(
                    'FQNS' => 'EventEspresso\PayPalSmartButtons',
                    'DIR'  => __DIR__,
                ),
				'admin_callback' => 'additional_admin_hooks',
				// if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
				'pue_options'			=> array(
					'pue_plugin_slug' => 'espresso_paypal_smart_buttons',
					'plugin_basename' => EE_PAYPAL_SMART_BUTTONS_BASENAME,
					'checkPeriod' => '24',
					'use_wp_update' => FALSE,
					),
				'payment_method_paths' => array(
					EE_PAYPAL_SMART_BUTTONS_PATH . 'payment_methods' . DS . 'Paypal_Smart_Buttons',
					),
                'module_paths' => array(
                    EE_PAYPAL_SMART_BUTTONS_PATH . 'EED_Paypal_Smart_Buttons.module.php'
                )
		));
	}



	/**
	 * 	additional_admin_hooks
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function additional_admin_hooks() {
		// is admin and not in M-Mode ?
		if ( is_admin() && ! EE_Maintenance_Mode::instance()->level() ) {
			add_filter( 'plugin_action_links', array( $this, 'plugin_actions' ), 10, 2 );
		}
	}



	/**
	 * plugin_actions
	 *
	 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
	 * @param $links
	 * @param $file
	 * @return array
	 */
	public function plugin_actions( $links, $file ) {
		if ( $file == EE_PAYPAL_SMART_BUTTONS_BASENAME ) {
			// before other links
			array_unshift( $links, '<a href="admin.php?page=espresso_payment_settings">' . __('Settings') . '</a>' );
		}
		return $links;
	}






}
// End of file EE_Paypal_Smart_Buttons.class.php
// Location: wp-content/plugins/espresso-paypal-smart-buttons/EE_Paypal_Smart_Buttons.class.php
