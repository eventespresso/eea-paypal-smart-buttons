<?php

namespace EventEspresso\PayPalSmartButtons\payment_methods\Paypal_Smart_Buttons\forms;

use DomainException;
use EE_Billing_Info_Form;
use EE_Config;
use EE_Error;
use EE_Form_Section_HTML;
use EE_Form_Section_Proper;
use EE_Registration;
use EE_Template_Layout;
use EE_Hidden_Input;
use EE_Payment_Method;
use EE_Transaction;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use InvalidArgumentException;
use ReflectionException;

/**
 * Class PayPalSmartButtonBillingForm
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          1.0.0.p
 */
class PayPalSmartButtonBillingForm extends EE_Billing_Info_Form
{

	/**
	 * @var EE_Transaction
	 */
	protected $transaction;


	/**
	 * PayPalSmartButtonBillingForm constructor.
	 *
	 * @param EE_Payment_Method $payment_method
	 * @param EE_Transaction    $transaction
	 * @param array             $options_array
	 * @throws EE_Error
	 */
	public function __construct(
		EE_Payment_Method $payment_method,
		EE_Transaction $transaction,
		array $options_array = []
	) {
		$this->transaction = $transaction;
		$options_array     = array_replace_recursive(
			[
				$options_array,
				'subsections' => [
					'debug'         => new EE_Form_Section_Proper(
						[
							'layout_strategy' => new EE_Template_Layout(
								[
									'layout_template_file' => $payment_method->type_obj()->file_folder()
									                          . 'templates/paypal_smart_buttons_debug_info.template.php',
									'template_args'        => [
										'debug_mode' => $payment_method->debug_mode(),
									],
								]
							),
						]
					),
					'payment_div'   => new EE_Form_Section_HTML(
						'<div id="ee-paypal-button-container" class="wide-paypal-smart-buttons"></div>'
					),
					'payment_token' => new EE_Hidden_Input(
						[
							'html_id' => 'ee-paypal-payment-token',
						]
					),
					'payment_id'    => new EE_Hidden_Input(
						[
							'html_id' => 'ee-paypal-payment-id',
						]
					),
					'order_id'      => new EE_Hidden_Input(
						[
							'html_id' => 'ee-paypal-order-id',
						]
					),
					'payer_id'      => new EE_Hidden_Input(
						[
							'html_id' => 'ee-paypal-payer-id',
						]
					),
				],
			]
		);
		parent::__construct($payment_method, $options_array);
	}


	/**
	 * @return string
	 * @throws DomainException
	 * @since   $VID:$
	 */
	private function getProductionJsAssetsUrl()
	{
		$asset_manifest_file_path = EE_PAYPAL_SMART_BUTTONS_PATH . '/assets/dist/build-manifest.json';
		$asset_manifest_file      = file_get_contents($asset_manifest_file_path);
		$asset_manifest           = json_decode($asset_manifest_file, true);
		$production_js_file       = isset($asset_manifest['paypal-smart-buttons.js'])
			? $asset_manifest['paypal-smart-buttons.js']
			: '';
		if (! empty($production_js_file)) {
			return EE_PAYPAL_SMART_BUTTONS_URL . "assets/dist/$production_js_file";
		}
		throw new DomainException(
			sprintf(
				esc_html__(
					'The PayPal Smart Buttons Asset Manifest file is not readable or could not be found in %1$s',
					'event_espresso'
				),
				$asset_manifest_file_path
			)
		);
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
			$this->getProductionJsAssetsUrl(),
			['paypal_smart_buttons', 'jquery', 'single_page_checkout'],
			null,
			true
		);
		wp_enqueue_style(
			'ee_paypal_smart_buttons_style',
			EE_PAYPAL_SMART_BUTTONS_URL . '/css/ee_pp_smart_buttons.css',
			['single_page_checkout'],
			EE_PAYPAL_SMART_BUTTONS_VERSION
		);
		$event_name = esc_html__('event', 'event_espresso');
		if ($this->transaction instanceof EE_Transaction) {
			$primary_reg = $this->transaction->primary_registration();
			if ($primary_reg instanceof EE_Registration) {
				$event_name = $primary_reg->event_name();
			}
		}
		wp_localize_script(
			'ee_paypal_smart_buttons',
			'eePpSmartButtonsData',
			[
				'data'         => [
					'currency'                        => EE_Config::instance()->currency->code,
					// Round the amount to PayPal's expected 2 decimal places. Strangely enough, even if the currency
					// accepts NO decimal places, this is the format they want. So give it to them.
					'transaction_total'               => number_format($this->transaction->remaining(), 2, '.', ''),
					'payment_div_selector'            => '#ee-paypal-button-container',
					'sandbox_mode'                    => $this->_pm_instance->debug_mode(),
					'client_id'                       => $this->_pm_instance->get_extra_meta('client_id', true),
					'slug'                            => $this->_pm_instance->slug(),
					'button_shape'                    => $this->_pm_instance->get_extra_meta('button_shape', true),
					'nextButtonSelector'              => '#spco-go-to-step-finalize_registration-submit',
					'hiddenInputPayerIdSelector'      => '#ee-paypal-payer-id',
					'hiddenInputPaymentIdSelector'    => '#ee-paypal-payment-id',
					'hiddenInputPaymentTokenSelector' => '#ee-paypal-payment-token',
					'hiddenInputOrderIdSelector'      => '#ee-paypal-order-id',
					//phpcs:disable Generic.Files.LineLength.TooLong
					'shipping'                        => apply_filters(
						'FHEE__PayPalSmartButtonBillingForm__enqueue_js__eePpSmartButtonsData__data__shipping',
						'NO_SHIPPING',
						$this->transaction
					)
					//phpcs:enable
				],
				'translations' => [
					'no_SPCO_error'    => esc_html__(
						'It appears the Single Page Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.',
						'event_espresso'
					),
					'no_paypal_js'     => esc_html__(
						'It appears the Paypal Express Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.',
						'event_espresso'
					),
					'orderDescription' => apply_filters(
						'FHEE__PayPalSmartButtonBillingForm__enqueue_js__eePpSmartButtonsData__translations__orderDescription',
						sprintf(
						// translators: 1: event name, 2: site name
							esc_html_x(
								'Event Registrations for %1$s from %2$s',
								'Event Registrations for Event Name from Site Name',
								"event_espresso"
							),
							$event_name,
							wp_specialchars_decode(get_bloginfo(), ENT_QUOTES)
						),
						$event_name,
						$this->transaction,
						$this->_pm_instance
					),
				],
			]
		);
	}
}
// End of file PayPalSmartButtonBillingForm.php
// Location: EventEspresso\PayPalSmartButtons\payment_methods\Paypal_Smart_Buttons\forms/PayPalSmartButtonBillingForm.php
