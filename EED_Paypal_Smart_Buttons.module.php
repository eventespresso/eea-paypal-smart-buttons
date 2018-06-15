<?php

use EventEspresso\core\exceptions\EntityNotFoundException;

/**
 * Class EED_Paypal_Smart_Buttons
 *
 * Description
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          $VID:$
 *
 */
class EED_Paypal_Smart_Buttons extends EED_Module
{
    public static function set_hooks()
    {
        add_action('wp_enqueue_scripts', array('EED_Paypal_Smart_Buttons','enqueuePayPalExpressCheckoutJS'), 200);
        add_filter(
            'FHEE__EE_Radio_Button_Display_Strategy__display',
            array(
                'EED_Paypal_Smart_Buttons',
                'addPayPalExpressCheckoutDiv',
            ),
            10,
            3
        );
        add_filter(
            'FHEE__EE_Radio_Button_Display_Strategy__display__option_label_class',
            array(
                'EED_Paypal_Smart_Buttons',
                'hideNormalPaymentButton'
            ),
            10,
            4
        );

    }

    public static function set_hooks_admin()
    {
        add_filter(
            'FHEE__EE_Radio_Button_Display_Strategy__display',
            array(
                'EED_Paypal_Smart_Buttons',
                'addPayPalExpressCheckoutDiv',
            ),
            10,
            3
        );
    }


    /**
     * Enqueues the JS and CSS needed for the buttons
     */
    public static function enqueuePayPalExpressCheckoutJS()
    {
        // fetch the current transaction
        $current_transaction = self::getCurrentTransaction();
        if (! $current_transaction instanceof EE_Transaction) {
            return;
        }
        $paypal_smart_button_pm = self::getCurrentPaypalSmartButtonPM($current_transaction);
        if (! $paypal_smart_button_pm instanceof EE_Payment_Method) {
            return;
        }
        wp_register_script('paypal_smart_buttons', 'https://www.paypalobjects.com/api/checkout.js');
        wp_enqueue_script(
            'ee_paypal_smart_buttons',
            EE_PAYPAL_SMART_BUTTONS_URL . '/scripts/ee_pp_smart_buttons.js',
            array('paypal_smart_buttons', 'jquery', 'espresso_core', 'single_page_checkout'),
            EE_PAYPAL_SMART_BUTTONS_VERSION,
            true
        );
        wp_enqueue_style(
            'ee_paypal_smart_buttons_style',
            EE_PAYPAL_SMART_BUTTONS_URL . '/css/ee_pp_smart_buttons.css',
            array('single_page_checkout'),
            EE_PAYPAL_SMART_BUTTONS_VERSION
        );

        $show_big_buttons = $paypal_smart_button_pm->get_extra_meta('button_size', true) === 'full_width';
        wp_localize_script(
            'ee_paypal_smart_buttons',
            'ee_paypal_smart_buttons_args',
            array(
                'currency' => EE_Config::instance()->currency->code,
                'transaction_total' => $current_transaction->remaining(),
                'payment_div_selector' => '#paypal-button-container',
                'sandbox_mode' => $paypal_smart_button_pm->debug_mode(),
                'client_id' => $paypal_smart_button_pm->get_extra_meta('client_id',true),
                'slug' => $paypal_smart_button_pm->slug(),
                'button_shape' => $paypal_smart_button_pm->get_extra_meta('button_shape', true),
                'button_layout' => $show_big_buttons ? 'vertical' : 'horizontal'
            )
        );
    }


    /**
     * Adds the DIV where the smart button will appear
     * @param $html
     * @param $display_strategy_instance
     * @param $input
     * @return string
     */
    public static function addPayPalExpressCheckoutDiv($html, $display_strategy_instance, $input)
    {

        if($input->parent_section()->name() === 'available_payment_methods') {
            $payment_method = self::getCurrentPaypalSmartButtonPM(self::getCurrentTransaction());
            if($payment_method->get_extra_meta('button_size', true) === 'full_width') {
                $html_class = 'wide-paypal-smart-buttons';
            } else {
                $html_class = 'narrow-paypal-smart-buttons';
            }
            $html = '<div id="paypal-button-container" class="' . $html_class . '"></div>' . $html;
        }
        return $html;
    }


    /**
     * @param $input_args
     * @param $input
     */
    public static function hideNormalPaymentButton($html_class, $display_strategy, $input, $value)
    {
        if( ! $input instanceof EE_Radio_Button_Input
            || $input->html_name() !== 'selected_method_of_payment'
            || ! $value
        ) {
            //this isn't for a radio input with the right name, so forget about it
            return $html_class;
        }
        $is_paypal_smart_button_pm = EEM_Payment_Method::instance()->exists(
            array(
                array(
                    'PMD_type' => 'Paypal_Smart_Buttons',
                    'PMD_slug' => $value
                )
            )
        );
        if ($is_paypal_smart_button_pm) {
            $html_class .= ' hidden-paypal-smart-button-regular-button';
        }
        return $html_class;
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
        if( ! $transaction) {
            return null;
        }

        $applicable_payment_methods = EEM_Payment_Method::instance()->get_all_for_transaction(
            $transaction,
            EEM_Payment_Method::scope_cart
        );
        $paypal_smart_button_pm = null;
        foreach($applicable_payment_methods as $payment_method) {
            if($payment_method->type() === 'Paypal_Smart_Buttons') {
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
}
// End of file EED_Paypal_Smart_Buttons.php
// Location: ${NAMESPACE}/EED_Paypal_Smart_Buttons.php
