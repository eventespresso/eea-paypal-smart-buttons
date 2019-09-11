<?php

namespace EventEspresso\PayPalSmartButtons\payment_methods\Paypal_Smart_Buttons\forms;

use EE_Billing_Info_Form;
use EE_Config;
use EE_Error;
use EE_Form_Section_HTML;
use EE_Form_Section_Proper;
use EE_Template_Layout;
use EE_Hidden_Input;
use EE_Payment_Method;
use EE_Transaction;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\assets\Registry;
use EventEspresso\core\services\loaders\LoaderFactory;
use EventEspresso\core\services\payment_methods\gateways\GatewayDataFormatter;
use InvalidArgumentException;
use ReflectionException;

/**
 * Class PayPalSmartButtonBillingForm
 *
 * Description
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          1.0.0.p
 *
 */
class PayPalSmartButtonBillingForm extends EE_Billing_Info_Form
{

    /**
     * @var EE_Transaction
     */
    protected $transaction;

    protected $registry;

    /**
     * PayPalSmartButtonBillingForm constructor.
     *
     * @param EE_Payment_Method $payment_method
     * @param EE_Transaction $transaction
     * @param array $options_array
     * @param Registry|null $registry
     */
    public function __construct(
        EE_Payment_Method $payment_method,
        EE_Transaction $transaction,
        array $options_array = array(),
        Registry $registry = null
    ) {
        if (!$registry instanceof Registry) {
            $registry = LoaderFactory::getLoader()->getShared('EventEspresso\core\services\assets\Registry');
        }
        $this->registry = $registry;
        $this->transaction = $transaction;
        $options_array = array_replace_recursive(
            array(
                $options_array,
                'subsections' => array(
                    'debug' => new EE_Form_Section_Proper(
                        array(
                            'layout_strategy' => new EE_Template_Layout(
                                array(
                                    'layout_template_file' => $payment_method->type_obj()->file_folder()
                                                                . 'templates/paypal_smart_buttons_debug_info.template.php',
                                    'template_args'        => array(
                                        'debug_mode' => $payment_method->debug_mode(),
                                    )
                                )
                            )
                        )
                    ),
                    'payment_div' => new EE_Form_Section_HTML(
                        '<div id="ee-paypal-button-container" class="wide-paypal-smart-buttons"></div>'
                    ),
                    'payment_token' => new EE_Hidden_Input(
                        array(
                            'html_id' => 'ee-paypal-payment-token',
                        )
                    ),
                    'payment_id' => new EE_Hidden_Input(
                        array(
                            'html_id' => 'ee-paypal-payment-id',
                        )
                    ),
                    'order_id' => new EE_Hidden_Input(
                        array(
                            'html_id' => 'ee-paypal-order-id'
                        )
                    ),
                    'payer_id' => new EE_Hidden_Input(
                        array(
                            'html_id' => 'ee-paypal-payer-id'
                        )
                    ),
                )
            )
        );
        parent::__construct($payment_method, $options_array);
    }


    /**
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    // @codingStandardsIgnoreStart
    public function enqueue_js()// @codingStandardsIgnoreEnd
    {
        parent::enqueue_js();
        // enqueue PayPal's javascript library
        // see https://github.com/paypal/paypal-checkout/tree/master/docs for documentation on it
        wp_register_script('paypal_smart_buttons', 'https://www.paypalobjects.com/api/checkout.js');
        wp_enqueue_script(
            'ee_paypal_smart_buttons',
            $this->registry->getAssetUrl('ee-paypal-smart-buttons', 'paypal-smart-buttons', 'js'),
            array('paypal_smart_buttons', 'jquery', 'espresso_core', 'single_page_checkout', 'eejs-core'),
            null,
            true
        );
        wp_enqueue_style(
            'ee_paypal_smart_buttons_style',
            EE_PAYPAL_SMART_BUTTONS_URL . '/css/ee_pp_smart_buttons.css',
            array('single_page_checkout'),
            EE_PAYPAL_SMART_BUTTONS_VERSION
        );

        $event_name = esc_html__('event', 'event_espresso');
        if ($this->transaction instanceof EE_Transaction) {
            $primary_reg = $this->transaction->primary_registration();
            if($primary_reg instanceof EE_Registration){
                $event_name = $primary_reg->event_name();
            }
        };
        $this->registry->addData(
            'paypalSmartButtons',
            [
                'data' => [
                    'currency' => EE_Config::instance()->currency->code,
                    // Round the amount to PayPal's expected 2 decimal places. Strangely enough, even if the currency
                    // accepts NO decimal places, this is the format they want. So give it to them.
                    'transaction_total' => number_format($this->transaction->remaining(), 2),
                    'payment_div_selector' => '#ee-paypal-button-container',
                    'sandbox_mode' => $this->_pm_instance->debug_mode(),
                    'client_id' => $this->_pm_instance->get_extra_meta('client_id', true),
                    'slug' => $this->_pm_instance->slug(),
                    'button_shape' => $this->_pm_instance->get_extra_meta('button_shape', true),
                    'nextButtonSelector' => '#spco-go-to-step-finalize_registration-submit',
                    'hiddenInputPayerIdSelector' => '#ee-paypal-payer-id',
                    'hiddenInputPaymentIdSelector' => '#ee-paypal-payment-id',
                    'hiddenInputPaymentTokenSelector' => '#ee-paypal-payment-token',
                    'hiddenInputOrderIdSelector' => '#ee-paypal-order-id',
                ],
                'translations' => [
                    'no_SPCO_error' => esc_html__('It appears the Single Page Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.', 'event_espresso'),
                    'no_paypal_js' => esc_html__('It appears the Paypal Express Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.', 'event_espresso'),
                    'orderDescription' => sprintf(
                        __('Event Registrations from %1$s for %2$s', "event_espresso"),
                        wp_specialchars_decode(get_bloginfo(), ENT_QUOTES),
                        $event_name
                    ),
                ]
            ]
        );
    }
}
// End of file PayPalSmartButtonBillingForm.php
// Location: EventEspresso\PayPalSmartButtons\payment_methods\Paypal_Smart_Buttons\forms/PayPalSmartButtonBillingForm.php
