/**
 * @var ee_paypal_smart_button_args array of localized variables
 */
jQuery(document).ready(function () {
    //add SPCO object
    ee_paypal_smart_buttons_args.data.spco = SPCO;
    //create the smart buttons object
    ee_paypal_smart_buttons = new EegPayPalSmartButtons(ee_paypal_smart_buttons_args.data, ee_paypal_smart_buttons_args.translations);
    //and set it up to listen for its cue to get initialized
    ee_paypal_smart_buttons.set_init_listeners();
});

/**
 *
 * @param instance_vars {
 * 	spco : SPCO,
 *	currency : string (eg USD)
 *	transaction_total: float|string
 *	payment_div_selector : string	eg '#paypal-button-container'
 *  next_button_selector : string eg '.spco-next-step-btn'
 *	sandbox_mode: boolean
 *	client_id: string
 *	slug: string (payment method slug)
 *	button_shape: string
 *	button-size: string
 *	hidden_input_payer_id_selector: string
 *  hidden_input_payment_id_selector: string
 *  hidden_input_payment_token_selector: string
 *  hidden_input_order_id_selector: string
 * }
 * @param translations {
 * 	no_SPCO_error: string
 * 	no_paypal_js: string
 * }
 * @constructor
 */
function EegPayPalSmartButtons(instance_vars, translations) {
    this.spco = instance_vars.spco;
    this.currency = instance_vars.currency;
    this.transaction_total = instance_vars.transaction_total;
    this.payment_div_selector = instance_vars.payment_div_selector;
    this.sandbox_mode = instance_vars.sandbox_mode;
    this.client_id = instance_vars.client_id;
    this.slug = instance_vars.slug;
    this.button_shape = instance_vars.button_shape;
    this.next_button_selector = instance_vars.next_button_selector;
    this.hidden_input_payer_id_selector = instance_vars.hidden_input_payer_id_selector;
    this.hidden_input_payment_id_selector = instance_vars.hidden_input_payment_id_selector;
    this.hidden_input_payment_token_selector = instance_vars.hidden_input_payment_token_selector;
    this.hidden_input_order_id_selector = instance_vars.hidden_input_order_id_selector;

    this.initialized = false;
    this.translations = translations;

    this.payment_div = null;
    this.next_button = null;
    this.hidden_input_payer_id = null;
    this.hidden_input_payment_id = null;
    this.hidden_input_payment_token = null;
    this.hidden_input_order_id = null;

    /**
     * Sets listeners that will trigger initializing the smart buttons.
     */
    this.set_init_listeners = function () {
        this.set_listener_for_payment_method_selector();
        this.set_listener_for_display_spco();
        this.set_listener_for_payment_amount_change();
        //also, if the page was reloaded on the payment option step, we should initialize immediately
        if (this.billing_form_loaded()) {
            this.initialize();
        }
    };

    /**
     * When SPCO displays a step, if its the payment options step, and our billing
     * form is present, initialize the smart buttons
     *
     */
    this.set_listener_for_display_spco = function () {
        this.spco.main_container.on('spco_display_step', (event, step_to_show) => {
            if (typeof step_to_show !== 'undefined'
                && step_to_show === 'payment_options'
                && this.billing_form_loaded()
            ) {
                this.initialize();
            }
        });
    };

    /**
     * When they switch payment methods, if the payment method is this one,
     * initialize the smart button (or if it's already initialized, just show it again).
     * If they selected a different payment method, hide the smart buttons
     */
    this.set_listener_for_payment_method_selector = function () {
        this.spco.main_container.on('spco_switch_payment_methods', (event, payment_method) => {
            //SPCO.console_log( 'payment_method', payment_method, false );
            if (typeof payment_method !== 'undefined' && payment_method === this.slug) {
                this.initialize();
            } else {
                //and if this was previously initialized, make sure we hide the button
                if (this.initialized) {
                    this.hide_smart_buttons();
                }
            }
        });
    };

    /**
     * Returns true if this payment method's billing form exists on the page
     * @return {boolean}
     */
    this.billing_form_loaded = function () {
        return jQuery(this.hidden_input_payment_id_selector).length > 0;
    };

    /**
     * Initializes jQuery selected objects so we don't need to query for anything afterwards
     */
    this.initialize_objects = function () {
        this.next_button = jQuery(this.next_button_selector);
        this.payment_div = jQuery(this.payment_div_selector);
        this.hidden_input_payer_id = jQuery(this.hidden_input_payer_id_selector);
        this.hidden_input_payment_id = jQuery(this.hidden_input_payment_id_selector);
        this.hidden_input_payment_token = jQuery(this.hidden_input_payment_token_selector);
        this.hidden_input_order_id = jQuery(this.hidden_input_order_id_selector);
    };

    /**
     * Shows the smart buttons (this may require initializing them) and otherwise initializes this object
     */
    this.initialize = function () {
        if (typeof this.spco === 'undefined') {
            this.hide_smart_buttons();
            this.spco.show_event_queue_ajax_msg('error', this.translations.no_SPCO_error, this.spco.notice_fadeout_attention, true);
            return;
        }
        // ensure that the StripeCheckout js class is loaded
        if (typeof paypal === 'undefined') {
            this.spco.show_event_queue_ajax_msg('error', this.translations.no_paypal_js, this.spco.notice_fadeout_attention, true);
            return;
        }

        if (!this.initialized) {
            this.initialize_objects();
        }
        this.show_smart_buttons();
    };

    /**
     * When the payment amount changes, just update this object's transaction_total
     */
    this.set_listener_for_payment_amount_change = function () {
        this.spco.main_container.on('spco_payment_amount', (event, payment_amount) => {
            if (typeof payment_amount !== 'undefined' && parseInt(payment_amount) !== 0) {
                this.transaction_total = payment_amount;
            }
        });
    };


    /**
     * Hide the smart buttons and show the normal "Proceed with payment" button.
     * Done when this payment method is de-selected
     */
    this.hide_smart_buttons = function () {
        this.next_button.show();
        if (this.payment_div.length > 0) {
            this.payment_div.hide();
        }
    };

    /**
     * Show the smart button (if it hasn't yet been initialized, initialize it)
     * and hide the normal "proceed to finalize payment" button
     */
    this.show_smart_buttons = function () {
        if (!this.initialized) {
            this.initialize_smart_buttons();
        } else {
            this.payment_div.show();
        }
        this.next_button.hide();
    };


    this.initialize_smart_buttons = function () {
        this.initialized = true;
        //move the paypal button to the right spot
        this.payment_div.insertBefore(this.next_button);
        var result = paypal.Button.render({

            // Set your environment

            env: this.sandbox_mode ? 'sandbox' : 'production', // sandbox | production

            // Specify the style of the button
            // locale: 'en_BR',
            style: {
                layout: 'vertical',  // horizontal | vertical
                size: 'responsive',    // small, medium | large | responsive
                shape: this.button_shape,      // pill | rect
            },

            // PayPal Client IDs - replace with your own
            // Create a PayPal app: https://developer.paypal.com/developer/applications/create
            client: {
                sandbox: this.client_id,
                production: this.client_id
            },

            payment: (data, actions) => {
                return actions.payment.create({
                    payment: {
                        //documentation on what format transactions can take: https://developer.paypal.com/docs/api/payments/#definition-transaction
                        transactions: [
                            {
                                amount: {total: this.transaction_total, currency: this.currency}
                            }
                        ],
                    },
                    meta: {
                        partner_attribution_id: 'EventEspresso_SP'
                    }
                });
            },

            onAuthorize: (data, actions) => {
                // don't execute the payment here. Let's do it server-side where it's more secure
                this.hidden_input_payer_id.val(data.payerID);
                this.hidden_input_payment_id.val(data.paymentID);
                this.hidden_input_payment_token.val(data.paymentToken);
                this.hidden_input_order_id.val(data.orderID);
                this.next_button.trigger('click');
            },
            onError: (data, actions) => {
                if (typeof(data) !== 'undefined' && typeof(data.message) !== 'undefined') {
                    var error = data.message;
                } else {
                    var error = 'An error occurred while processing payment with PayPal';
                }
                var messages = {
                    errors: error
                };
                this.spco.display_messages(messages, false);
            }

        }, this.payment_div_selector);

    };
}

