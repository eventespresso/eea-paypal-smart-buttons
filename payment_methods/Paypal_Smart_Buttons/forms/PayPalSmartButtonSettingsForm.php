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
 * @since          1.0.0.p
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
                    // store the access token like other extra meta inputs
                    // except hide it, because we don't ask users for it directly. We will retrieve it from
                    // PayPal upon form submission
                    'access_token' => new EE_Hidden_Input()
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
        // also, let's check the credentials are valid.
        $valid_data = $this->valid_data();
        if (isset($valid_data['PMD_debug_mode'], $valid_data['client_id'], $valid_data['secret'])) {
            try {
                $valid_data_access_token = !empty($valid_data['access_token']) ? $valid_data['access_token'] : null;
                $api_client = new PayPalRestApiClient(
                    $valid_data['PMD_debug_mode'],
                    $valid_data['client_id'],
                    $valid_data['secret'],
                    $valid_data_access_token
                );
                // Refresh the token manually in case the keys were changed.
                // If this is an initial setup the token will be auto refreshed/created.
                if (! empty($valid_data_access_token)) {
                    $api_client->refreshAccessToken();
                }
                $access_token = $api_client->getAccessToken();
                $this->populate_defaults(
                    array(
                        'access_token' => $access_token
                    )
                );
            } catch (EE_Error $e) {
                if($e->getMessage() === 'Unauthorized') {
                    $message = esc_html__('PayPal did not accept your API credentials. Double-check your credentials and copy and paste them into the fields again and save changes. Alternatively, create a new app and try the new API credentials.', 'event_espresso');
                    // Give them a few more tips inline.
                    $this->get_input('PMD_debug_mode')->add_validation_error(
                        esc_html__('If you are using PayPal Sandbox (test) credentials, Debug mode should be set to "Yes". Otherwise, if you are using live PayPal credentials, set this to "No".', 'event_espresso')
                    );
                    $this->get_input('client_id')->add_validation_error(
                        esc_html__('Are you sure this is your REST API App Client ID, not your login username?%1$s', 'event_espresso')
                    );
                    $this->get_input('secret')->add_validation_error(
                        esc_html__('Are you sure this is your REST API App Client ID, not your login password?', 'event_espresso')
                    );
                } else  {
                    $message = esc_html(
                        sprintf(
                            _x(
                                'Error validating PayPal credentials. %1$s',
                                'Error validating PayPal credentials. Error description',
                                'event_espresso'
                            ),
                            $e->getMessage()
                        )
                    );
                }
                $this->add_validation_error($message);
            }
        }
    }
}
// End of file PayPalSmartButtonSettingsForm.php
// Location: EventEspresso\caffeinated\payment_methods\Paypal_Smart_Buttons\forms/PayPalSmartButtonSettingsForm.php
