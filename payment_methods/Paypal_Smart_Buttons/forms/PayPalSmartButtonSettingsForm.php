<?php

namespace EventEspresso\PayPalSmartButtons\payment_methods\Paypal_Smart_Buttons\forms;

use EE_Error;
use EE_Hidden_Input;
use EE_Payment_Method_Form;
use EE_Select_Input;
use EE_Text_Input;
use EEG_Paypal_Smart_Buttons;
use EventEspresso\PayPalSmartButtons\core\services\PayPalRestApiClient;
use WP_Error;

/**
 * Class PayPalSmartButtonSettingsForm
 *
 * Form for the PayPal Smart Buttons payment method. One special bit of functionality: it validates
 * the user's client ID and secret, and if they work, stores the authorization token which will be used
 * for REST API requests.
 * See https://developer.paypal.com/docs/api/overview/#get-an-access-token for how we do this.
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          $VID:$
 *
 */
class PayPalSmartButtonSettingsForm extends EE_Payment_Method_Form
{

    public function __construct($help_tab_link, array $options_array = array())
    {
        $options_array = array_merge_recursive(
            $options_array,
            array(
                'extra_meta_inputs' => array(
                    'client_id'    => new EE_Text_Input(
                        array(
                            'html_label_text' => sprintf(
                                _x(
                                    'PayPal REST API App Client ID %s',
                                    'PayPal REST API App Client ID (help link)',
                                    'event_espresso'
                                ),
                                $help_tab_link
                            ),
                            'required'        => true,
                        )
                    ),
                    'secret'       => new EE_Text_Input(
                        array(
                            'html_label_text' => sprintf(
                                _x(
                                    'PayPal REST API App Secret %s',
                                    'PayPal REST API App Secret (help link)',
                                    'event_espresso'
                                ),
                                $help_tab_link
                            ),
                            'required'        => true,
                        )
                    ),
                    'button_shape' => new EE_Select_Input(
                        array(
                            'pill' => esc_html__('Pill (Recommended)', 'event_espresso'),
                            'rect' => esc_html__('Rectangular', 'event_espresso'),
                        ),
                        array(
                            'html_label_text' => esc_html__('Button Shape', 'event_espresso'),
                            'default'         => 'pill',
                        )
                    ),
                    'button_size'  => new EE_Select_Input(
                        array(
                            'standard'     => esc_html__('Standard', 'event_espresso'),
                            'full_width'      => esc_html__('Full Page Width', 'event_espresso'),
                        ),
                        array(
                            'html_label_text' => esc_html__('Button Size', 'event_espresso'),
                            'default'         => 'full_width',
                            'html_help_text' => esc_html__('Standard button size only has room to show the pay with PayPal or PayPal Credit options. Full Width can show other options, like credit cards and Venmo, when applicable.', 'event_espresso')
                        )
                    ),
                    // store the access token like other extra meta inputs
                    // except hide it, because we don't ask users for it directly. We will retrieve it from
                    // PayPal upon form submission
                    'access_token' => new EE_Hidden_Input()
                ),
                // button URL and "open by default" don't apply because PayPal's javascript generates the button,
                // and users should never see this payment method selected normally like others
                // (when they click PayPal's button, the pop-up window immediately appears, no need for another click)
                'exclude'           => array(
                    'PMD_button_url',
                    'PMD_open_by_default'
                ),
            )
        );
        parent::__construct($options_array);
    }


    /**
     * @return bool|void
     * @throws \EE_Error
     */
    public function _validate()
    {
        parent::_validate();
        //also, let's check the credentials are valid.
        $valid_data = $this->valid_data();
        if (isset($valid_data['debug_mode'], $valid_data['client_id'], $valid_data['secret'])) {
            try {
                $api_client = new PayPalRestApiClient(
                    $valid_data['debug_mode'],
                    $valid_data['client_id'],
                    $valid_data['secret']
                );
                $access_token = $api_client->getAccessToken();
                $this->populate_defaults(
                    array(
                        'access_token' => $access_token
                    )
                );
            } catch (EE_Error $e) {
                $this->add_validation_error(
                    esc_html(
                        sprintf(
                            _x(
                                'Error validating PayPal credentials. %1$s',
                                'Error validating PayPal credentials. Error description',
                                'event_espresso'
                            ),
                            $e->getMessage()
                        )
                    )
                );
            }
        }
    }
}
// End of file PayPalSmartButtonSettingsForm.php
// Location: EventEspresso\caffeinated\payment_methods\Paypal_Smart_Buttons\forms/PayPalSmartButtonSettingsForm.php
