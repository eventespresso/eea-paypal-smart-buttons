<?php
/*
  Plugin Name: Event Espresso - PayPal Express Checkout with Smart Buttons (EE 4.9.64+)
  Plugin URI: http://www.eventespresso.com
  Description: The Event Espresso Paypal Express Checkout with Smart Buttons add-on adds a new off-site payment method with a payment pop-up, and dynamically adds buttons to pay with PayPal Credit, Credit Card, Venmo, etc based on the registrant's location.
  Version: 1.0.2.rc.000
  Author: Event Espresso
  Author URI: http://www.eventespresso.com
  Copyright 2014 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version	 	EE4
 *
 * ------------------------------------------------------------------------
 */
define('EE_PAYPAL_SMART_BUTTONS_VERSION', '1.0.2.rc.000');
define('EE_PAYPAL_SMART_BUTTONS_PLUGIN_FILE', __FILE__);
function load_espresso_paypal_smart_buttons()
{
    if (class_exists('EE_Addon')) {
        // paypal_smart_buttons version
        require_once(plugin_dir_path(__FILE__) . 'EE_Paypal_Smart_Buttons.class.php');
        EE_Paypal_Smart_Buttons::register_addon();
    }
}
add_action('AHEE__EE_System__load_espresso_addons', 'load_espresso_paypal_smart_buttons');

// End of file espresso_paypal_smart_buttons.php
// Location: wp-content/plugins/espresso-paypal-smart-buttons/espresso_paypal_smart_buttons.php
