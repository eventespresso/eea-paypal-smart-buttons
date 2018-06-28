<?php

namespace EventEspresso\PayPalSmartButtons\core\services;

use EE_Error;
use WP_Error;

/**
 * Class PayPalRestApi
 *
 * Description
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          1.0.0.p
 *
 */
class PayPalRestApiClient
{

    protected $sandbox_server;

    protected $client_id;

    protected $secret;

    protected $access_token;

    /**
     * PayPalRestApi constructor.
     *
     * @param boolean $sandbox_server
     * @param string $client_id
     * @param string $secret
     * @param string $access_token
     */
    public function __construct($sandbox_server, $client_id, $secret, $access_token = null)
    {
        $this->sandbox_server = $sandbox_server;
        $this->client_id = $client_id;
        $this->secret = $secret;
        $this->access_token = !empty($access_token) ? $access_token : $this->refreshAccessToken();
    }


    /**
     * Gets the PayPal server API calls should be sent to
     * @return string
     */
    public function getServerName()
    {
        if ($this->sandbox_server) {
            return 'https://api.sandbox.paypal.com';
        } else {
            return 'https://api.paypal.com';
        }
    }


    /**
     * Given a payer and payment ID, executes/finalizes the payment (ie, causes funds to actually be transferred between
     * accounts). This also takes care of API responses which indicate a new access token is required (if so,
     * another API call is issued to get a new access token, and then the original request is retried).
     * May have the side effect of refreshing the access token
     * @param      $payer_id
     * @param      $payment_id
     * @param bool $retry
     * @return array
     * @throws EE_Error
     */
    public function executePayment($payer_id, $payment_id, $retry = false)
    {
        $post_body = array(
            'payer_id' => $payer_id
        );
        $url = $this->getServerName() . '/v1/payments/payment/' . $payment_id . '/execute/';
        $json = wp_json_encode($post_body);
        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'PayPal-Partner-Attribution-Id' => 'EventEspresso_SP'
                ),
                'body' => $json
            )
        );
        try {
            return $this->getPayPalResponseJson($response);
        } catch (EE_Error $e) {
            // if we're already in the middle of a retry, bubble up the error. We don't know how to deal with it
            if ($retry) {
                throw $e;
            }
            // ok we're going to get a new access token and try again, but only once...
            // and if we get an error, we're going to let it bubble up
            $this->refreshAccessToken();
            return self::executePayment(
                $payer_id,
                $payment_id,
                true
            );
        }
    }


    /**
     * Given the client ID and secret, retrieves the access token from PayPal
     * @param $sandbox_mode
     * @param $client_id
     * @param $secret
     * @throws EE_Error if there is a problem, otherwise sets the access token on this class
     *                  (you can use getAccessToken to retrieve it)
     */
    public function refreshAccessToken()
    {
        $base_url = $this->getServerName();
        $response = wp_remote_post(
            $base_url . '/v1/oauth2/token',
            array(
                'headers' => array(
                    'Accept'        => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->secret),
                ),
                'body'    => array(
                    'grant_type' => 'client_credentials',
                ),
            )
        );
        $response_json = $this->getPayPalResponseJson($response);
        if (! isset($response_json['access_token'])) {
            throw new EE_Error(
                esc_html__('No access token provided in response from PayPal', 'event_espresso')
            );
        }
        $this->access_token = $response_json['access_token'];
    }


    /**
     * Gets the access token that's been set
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }


    /**
     * Verifies the response isn't a WP_Error, and that it contains valid JSON, and doesn't contain an error
     * in that JSON. You can trust this returns an array, and it doesn't have PayPal's error data in it, but
     * any further validation relating to your request must be done elsewhere.
     * @param array|WP_Error $response result of one of wp_remote_request or wp_remote_post function calls
     * @throws EE_Error if there are any problems returns an array from the response's JSON
     * @return array from the JSON
     */
    protected function getPayPalResponseJson($response)
    {
        if (is_wp_error($response)) {
            /**
             * @var $response WP_Error
             */
            throw new EE_Error(
                $response->get_error_message()
            );
        }
        if (isset(
            $response['response'],
            $response['response']['code'],
            $response['response']['message']
        )
            && ($response['response']['code'] === 500 || $response['response']['code'] === 401)
        ) {
            throw new EE_Error(
                $response['response']['message']
            );
        }
        $response_body = wp_remote_retrieve_body($response);
        if (! $response_body) {
            throw new EE_Error(
                esc_html__(
                    'No response was received from PayPal',
                    'event_espresso'
                )
            );
        }
        $response_data = json_decode($response_body, true);
        if (! is_array($response_data)) {
            throw new EE_Error(
                esc_html__('No JSON body was received.', 'event_espresso')
            );
        }
        if (isset($response_data['error'], $response_data['error_description'])) {
            throw new EE_Error(
                esc_html(
                    sprintf(
                        _x(
                            '%1$s (%2$s)',
                            'Error message (error_code)',
                            'event_espresso'
                        ),
                        $response_data['error_description'],
                        $response_data['error']
                    )
                )
            );
        }
        return $response_data;
    }
}

// End of file PayPalRestApi.php
// Location: EventEspresso/PayPalRestApi.php
