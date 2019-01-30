/**
 * @var ee_paypal_smart_button_args array of localized variables
 */
let eePaypalSmartButtons = null;
jQuery( document ).ready( () => {
	//add SPCO object
	const eePpSmartButtonsData = eejsdata.data.paypalSmartButtons;
	eePpSmartButtonsData.data.spco = SPCO;
	//create the smart buttons object
	eePaypalSmartButtons = new EegPayPalSmartButtons( eePpSmartButtonsData.data, eePpSmartButtonsData.translations );
	//and set it up to listen for its cue to get initialized
	eePaypalSmartButtons.set_init_listeners();
} );

/**
 *
 * @param {Object} instanceVars {
 * 	spco : SPCO,
 *	currency : string (eg USD)
 *	transaction_total: float|string
 *	payment_div_selector : string	eg '#paypal-button-container'
 *  nextButtonSelector : string eg '.spco-next-step-btn'
 *	sandbox_mode: boolean
 *	client_id: string
 *	slug: string (payment method slug)
 *	button_shape: string
 *	button-size: string
 *	hiddenInputPayerIdSelector: string
 *  hiddenInputPaymentIdSelector: string
 *  hiddenInputPaymentTokenSelector: string
 *  hiddenInputOrderIdSelector: string
 * }
 * @param {Object} translations {
 * 	no_SPCO_error: string
 * 	no_paypal_js: string
 * }
 * @constructor
 */
function EegPayPalSmartButtons( instanceVars, translations ) {
	this.spco = instanceVars.spco;
	this.currency = instanceVars.currency;
	this.transaction_total = instanceVars.transaction_total;
	this.payment_div_selector = instanceVars.payment_div_selector;
	this.sandbox_mode = instanceVars.sandbox_mode;
	this.client_id = instanceVars.client_id;
	this.slug = instanceVars.slug;
	this.button_shape = instanceVars.button_shape;
	this.nextButtonSelector = instanceVars.nextButtonSelector;
	this.hiddenInputPayerIdSelector = instanceVars.hiddenInputPayerIdSelector;
	this.hiddenInputPaymentIdSelector = instanceVars.hiddenInputPaymentIdSelector;
	this.hiddenInputPaymentTokenSelector = instanceVars.hiddenInputPaymentTokenSelector;
	this.hiddenInputOrderIdSelector = instanceVars.hiddenInputOrderIdSelector;

	this.initialized = false;
	this.translations = translations;

	this.payment_div = null;
	this.next_button = null;
	this.hiddenInputPayerId = null;
	this.hiddenInputPaymentId = null;
	this.hiddenInputPaymentToken = null;
	this.hiddeIinputOrderId = null;

	/**
     * Sets listeners that will trigger initializing the smart buttons.
     */
	this.set_init_listeners = function() {
		this.set_listener_for_payment_method_selector();
		this.set_listener_for_display_spco();
		this.set_listener_for_payment_amount_change();
		//also, if the page was reloaded on the payment option step, we should initialize immediately
		if ( this.billing_form_loaded() ) {
			this.initialize();
		}
	};

	/**
     * When SPCO displays a step, if its the payment options step, and our billing
     * form is present, initialize the smart buttons
     *
     */
	this.set_listener_for_display_spco = function() {
		this.spco.main_container.on( 'spco_display_step', ( event, stepToShow ) => {
			if ( typeof stepToShow !== 'undefined' &&
                stepToShow === 'payment_options' &&
                this.billing_form_loaded()
			) {
				this.initialize();
			}
		} );
	};

	/**
     * When they switch payment methods, if the payment method is this one,
     * initialize the smart button (or if it's already initialized, just show it again).
     * If they selected a different payment method, hide the smart buttons
     */
	this.set_listener_for_payment_method_selector = function() {
		this.spco.main_container.on( 'spco_switch_payment_methods', ( event, paymentMethod ) => {
			//SPCO.console_log( 'payment_method', payment_method, false );
			if ( typeof paymentMethod !== 'undefined' && paymentMethod === this.slug ) {
				this.initialize();
			} else if ( this.initialized ) {
				//and if this was previously initialized, make sure we hide the button
				this.hide_smart_buttons();
			}
		} );
	};

	/**
     * Returns true if this payment method's billing form exists on the page
     * @return {boolean} whether it was successffully loaded or not.
     */
	this.billing_form_loaded = function() {
		return jQuery( this.hiddenInputPaymentIdSelector ).length > 0;
	};

	/**
     * Initializes jQuery selected objects so we don't need to query for anything afterwards
     */
	this.initialize_objects = function() {
		this.next_button = jQuery( this.nextButtonSelector );
		this.payment_div = jQuery( this.payment_div_selector );
		this.hiddenInputPayerId = jQuery( this.hiddenInputPayerIdSelector );
		this.hiddenInputPaymentId = jQuery( this.hiddenInputPaymentIdSelector );
		this.hiddenInputPaymentToken = jQuery( this.hiddenInputPaymentTokenSelector );
		this.hiddeIinputOrderId = jQuery( this.hiddenInputOrderIdSelector );
	};

	/**
     * Shows the smart buttons (this may require initializing them) and otherwise initializes this object
     */
	this.initialize = function() {
		if ( typeof this.spco === 'undefined' ) {
			this.hide_smart_buttons();
			this.spco.show_event_queue_ajax_msg( 'error', this.translations.no_SPCO_error, this.spco.notice_fadeout_attention, true );
			return;
		}
		// ensure that the StripeCheckout js class is loaded
		if ( typeof paypal === 'undefined' ) {
			this.spco.show_event_queue_ajax_msg( 'error', this.translations.no_paypal_js, this.spco.notice_fadeout_attention, true );
			return;
		}

		if ( ! this.initialized ) {
			this.initialize_objects();
		}
		this.show_smart_buttons();
	};

	/**
     * When the payment amount changes, just update this object's transaction_total
     */
	this.set_listener_for_payment_amount_change = function() {
		this.spco.main_container.on( 'spco_payment_amount', ( event, paymentAmount ) => {
			if ( typeof paymentAmount !== 'undefined' && parseInt( paymentAmount ) !== 0 ) {
				this.transaction_total = paymentAmount;
			}
		} );
	};

	/**
     * Hide the smart buttons and show the normal "Proceed with payment" button.
     * Done when this payment method is de-selected
     */
	this.hide_smart_buttons = function() {
		this.next_button.show();
		if ( this.payment_div.length > 0 ) {
			this.payment_div.hide();
		}
	};

	/**
     * Show the smart button (if it hasn't yet been initialized, initialize it)
     * and hide the normal "proceed to finalize payment" button
     */
	this.show_smart_buttons = function() {
		if ( ! this.initialized ) {
			this.initialize_smart_buttons();
		} else {
			this.payment_div.show();
		}
		this.next_button.hide();
	};

	this.initialize_smart_buttons = function() {
		this.initialized = true;
		//move the paypal button to the right spot
		this.payment_div.insertBefore( this.next_button );
		paypal.Button.render( {

			// Set your environment

			env: this.sandbox_mode ? 'sandbox' : 'production', // sandbox | production

			// Specify the style of the button
			// locale: 'en_BR',
			style: {
				layout: 'vertical', // horizontal | vertical
				size: 'responsive', // small, medium | large | responsive
				shape: this.button_shape, // pill | rect
			},

			// Change the wording on the PayPal popup
			// See: https://github.com/paypal/paypal-checkout/issues/554
			commit: true,

			// PayPal Client IDs - replace with your own
			// Create a PayPal app: https://developer.paypal.com/developer/applications/create
			client: {
				sandbox: this.client_id,
				production: this.client_id,
			},

			payment: ( data, actions ) => {
				return actions.payment.create( {
					payment: {
						//documentation on what format transactions can take: https://developer.paypal.com/docs/api/payments/#definition-transaction
						transactions: [
							{
								amount: { total: this.transaction_total, currency: this.currency },
							},
						],
					},
					meta: {
						partner_attribution_id: 'EventEspresso_SP',
					},
				} );
			},

			onAuthorize: ( data ) => {
				// don't execute the payment here. Let's do it server-side where it's more secure
				this.hiddenInputPayerId.val( data.payerID );
				this.hiddenInputPaymentId.val( data.paymentID );
				this.hiddenInputPaymentToken.val( data.paymentToken );
				this.hiddeIinputOrderId.val( data.orderID );
				// Wait a second before submitting in order to avoid accidentally submittinb before the values were updated.
				setTimeout( () => {
					this.next_button.trigger( 'click' );
				},
				1000 );
			},
			onError: ( data ) => {
				let error = null;
				if ( typeof( data ) !== 'undefined' && typeof( data.message ) !== 'undefined' ) {
					error = data.message;
				} else {
					error = 'An error occurred while processing payment with PayPal';
				}
				const messages = {
					errors: error,
				};
				this.spco.display_messages( messages, false );
			},

		}, this.payment_div_selector );
	};
}

