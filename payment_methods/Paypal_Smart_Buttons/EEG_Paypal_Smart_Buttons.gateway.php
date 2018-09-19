<?php

use EventEspresso\PayPalSmartButtons\core\services\PayPalRestApiClient;

/**
 * EEG_Paypal_Smart_Buttons
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 * ------------------------------------------------------------------------
 */
class EEG_Paypal_Smart_Buttons extends EE_Onsite_Gateway
{

    /**
     * @var $_access_token string
     */
    protected $_access_token;

    /**
     * @var $_client_id
     */
    protected $_client_id;

    /**
     * @var $_secret
     */
    protected $_secret;

    /**
     * @var array
     */
    protected $_currencies_supported = array(
        'USD',
        'GBP',
        'CAD',
        'AUD',
        'BRL',
        'CHF',
        'CZK',
        'DKK',
        'EUR',
        'HKD',
        'HUF',
        'ILS',
        'JPY',
        'MXN',
        'MYR',
        'NOK',
        'NZD',
        'PHP',
        'PLN',
        'SEK',
        'SGD',
        'THB',
        'TRY',
        'TWD',
        'RUB',
        'INR',
    );

    /**
     * @var PayPalRestApiClient
     */
    protected $rest_api_client;


    /**
     * Gets the REST API client to handling the exact communication
     * @return PayPalRestApiClient
     */
    protected function getClient()
    {
        if (! $this->rest_api_client instanceof PayPalRestApiClient) {
            $this->rest_api_client = new PayPalRestApiClient(
                $this->_debug_mode,
                $this->_client_id,
                $this->_secret,
                $this->_access_token
            );
        }
        return $this->rest_api_client;
    }


    /**
     * Checks if the access token stored before do_direct_payment was called was expired (based on whether the API
     * client needed to fetch a new one).
     * True if it was, false if it wasn't.
     *
     * @return bool
     */
    public function accessTokenWasExpired()
    {
        $freshest_access_token = $this->getClient()->getAccessToken();
        if (! empty($freshest_access_token)
            && $freshest_access_token !== $this->_access_token) {
            return true;
        }
        return false;
    }


    /**
     * Gets the latest access token (the API client may have found a new one during communication with PayPal)
     * @return string
     */
    public function getLatestAccessToken()
    {
        return $this->getClient()->getAccessToken();
    }

    /**
     * @param EEI_Payment $payment
     * @param array       $billing_info {
     * @type string $credit_card
     * @type string $credit_card_type
     * @type string $exp_month always 2 characters
     * @type string $exp_year always 4 characters
     * @type string $cvv
     * }
     * @see      parent::do_direct_payment for more info
     * @return EE_Payment|EEI_Payment
     * @throws EE_Error
     */
    public function do_direct_payment($payment, $billing_info = null)
    {
        $transaction = $payment->transaction();
        if (! $transaction instanceof EEI_Transaction) {
            throw new EE_Error(
                esc_html__('No transaction for payment while paying with PayPal Pro.', 'event_espresso')
            );
        }
        $primary_registrant = $transaction->primary_registration();
        if (! $primary_registrant instanceof EEI_Registration) {
            throw new EE_Error(
                esc_html__(
                    'No primary registration on transaction while paying with PayPal Pro.',
                    'event_espresso'
                )
            );
        }
        $attendee = $primary_registrant->attendee();
        if (! $attendee instanceof EEI_Attendee) {
            throw new EE_Error(
                esc_html__(
                    'No attendee on primary registration while paying with PayPal Pro.',
                    'event_espresso'
                )
            );
        }
        // @todo setup the items
        try {
            $payment_id = isset($billing_info['payment_id']) ? $billing_info['payment_id'] : '';
            $payment->set_txn_id_chq_nmbr($payment_id);
            $response_data = $this->getClient()->executePayment(
                isset($billing_info['payer_id']) ? $billing_info['payer_id'] : '',
                $payment_id
            );
            $this->log(
                array(
                    'response_data' => $response_data,
                    'billing_info' => $billing_info
                ),
                $payment
            );
        } catch (EE_Error $e) {
            $payment->set_status(
                $payment->set_status($this->_pay_model->failed_status())
            );
            $payment->set_gateway_response($e->getMessage());
            $payment->set_details($e->getTraceAsString());
            $this->log(
                array(
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                    'billing_info' => $billing_info
                ),
                $payment
            );
        }
        // key state should be approved
        // key transactions, first item, then amount, then total
        if (isset(
            $response_data['state'],
            $response_data['transactions'],
            $response_data['transactions'][0],
            $response_data['transactions'][0]['amount'],
            $response_data['transactions'][0]['amount']['total']
        )) {
            $payment->set_amount($response_data['transactions'][0]['amount']['total']);
            if ($response_data['state'] === 'approved') {
                $payment->set_status($this->_pay_model->approved_status());
            }
        } else {
            $payment->set_status(
                $payment->set_status($this->_pay_model->failed_status())
            );
            $payment->set_gateway_response(
                esc_html__('PayPal did not respond with the expected parameters.', 'event_espresso')
            );
            $payment->set_details(
                array(
                    'paypal_json_response' => $response_data
                )
            );
        }

        return $payment;
    }
}
