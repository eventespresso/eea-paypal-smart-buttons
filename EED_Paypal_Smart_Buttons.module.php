<?php

use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\services\loaders\LoaderFactory;

/**
 * Class EED_Paypal_Smart_Buttons
 *
 * Description
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          1.0.0.p
 *
 */
class EED_Paypal_Smart_Buttons extends EED_Module
{
    public static function set_hooks()
    {
        $this_module = new EED_Paypal_Smart_Buttons();
        add_action('init',array($this_module, 'enqueueScripts'));
    }

    public static function set_hooks_admin()
    {
    }


    /**
     * Gets the cart's current transaction or returns null
     * @return EE_Transaction|void
     */
    protected static function getCurrentTransaction()
    {
        $checkout = EED_Single_Page_Checkout::instance()->checkout;
        if (! $checkout instanceof EE_Checkout) {
            // we only want this JS during checkout
            return null;
        }
        // fetch the current transaction
        $current_transaction = EED_Single_Page_Checkout::instance()->transaction();
        if (! $current_transaction instanceof EE_Transaction) {
            return null;
        }
        return $current_transaction;
    }

    /**
     * Gets the current paypal smart buttons payment method, based on the transaction
     * @param $transaction
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     */
    protected static function getCurrentPaypalSmartButtonPM($transaction)
    {
        if (! $transaction) {
            return null;
        }

        $applicable_payment_methods = EEM_Payment_Method::instance()->get_all_for_transaction(
            $transaction,
            EEM_Payment_Method::scope_cart
        );
        $paypal_smart_button_pm = null;
        foreach ($applicable_payment_methods as $payment_method) {
            if ($payment_method->type() === 'Paypal_Smart_Buttons') {
                $paypal_smart_button_pm = $payment_method;
                break;
            }
        }
        if (! $paypal_smart_button_pm instanceof EE_Payment_Method) {
            // if the paypal smart button isn't active, we don't need to run this JS at all
            return null;
        }
        return $paypal_smart_button_pm;
    }

    /**
     *    run - initial module setup
     *    this method is primarily used for activating resources in the EE_Front_Controller thru the use of filters
     *
     * @access    public
     * @var            WP $WP
     * @return    void
     */
    public function run($WP)
    {
        // nothing to do here
    }

    public function enqueueScripts()
    {
        $registry = LoaderFactory::getLoader()->getShared('EventEspresso\core\services\assets\Registry');

        $registry->registerManifestFile(
            'eePaypalSmartButtons',
            plugin_dir_path(__FILE__) . '/assets/',
            plugin_dir_path(__FILE__) . '/assets/build/manifest.json'
        );
    }
}
// End of file EED_Paypal_Smart_Buttons.php
// Location: ${NAMESPACE}/EED_Paypal_Smart_Buttons.php
