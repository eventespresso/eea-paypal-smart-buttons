import paypal from 'paypal';
import warning from 'warning';
import jQuery from 'jquery';
import { data } from '@eventespresso/eejs';

/**
 * @var ee_paypal_smart_button_args array of localized variables
 */
let eePaypalSmartButtons = null;
jQuery( document ).ready( () => {
	//add SPCO object
	const eePpSmartButtonsData = data.paypalSmartButtons;
	eePpSmartButtonsData.data.spco = window.SPCO || null;
	eePpSmartButtonsData.data.paypal = paypal || null;
	//create the smart buttons object
	eePaypalSmartButtons = new EegPayPalSmartButtons( eePpSmartButtonsData.data, eePpSmartButtonsData.translations );
	//and set it up to listen for its cue to get initialized
	eePaypalSmartButtons.setInitListeners();
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
	this.paypal = instanceVars.paypal;
	this.currency = instanceVars.currency;
	this.transactionTotal = instanceVars.transaction_total;
	this.paymentDivSelector = instanceVars.payment_div_selector;
	this.sandboxMode = instanceVars.sandbox_mode;
	this.clientId = instanceVars.client_id;
	this.slug = instanceVars.slug;
	this.buttonShape = instanceVars.button_shape;
	this.nextButtonSelector = instanceVars.nextButtonSelector;
	this.hiddenInputPayerIdSelector = instanceVars.hiddenInputPayerIdSelector;
	this.hiddenInputPaymentIdSelector = instanceVars.hiddenInputPaymentIdSelector;
	this.hiddenInputPaymentTokenSelector = instanceVars.hiddenInputPaymentTokenSelector;
	this.hiddenInputOrderIdSelector = instanceVars.hiddenInputOrderIdSelector;

	this.initialized = false;
	this.translations = translations;

	this.paymentDiv = null;
	this.nextButton = null;
	this.hiddenInputPayerId = null;
	this.hiddenInputPaymentId = null;
	this.hiddenInputPaymentToken = null;
	this.hiddeIinputOrderId = null;

	/**
	 * Sets listeners that will trigger initializing the smart buttons.
	 */
	this.setInitListeners = function() {
		this.setListenerForPaymentMethodSelector();
		this.setListenerForDisplaySpco();
		this.setListenerForPaymentAmountChange();
		//also, if the page was reloaded on the payment option step, we should initialize immediately
		if ( this.billingFormLoaded() ) {
			this.initialize();
		}
	};

	/**
	 * When SPCO displays a step, if its the payment options step, and our billing
	 * form is present, initialize the smart buttons
	 *
	 */
	this.setListenerForDisplaySpco = function() {
		this.spco.main_container.on( 'spco_display_step', ( event, stepToShow ) => {
			if ( typeof stepToShow !== 'undefined' &&
				stepToShow === 'payment_options' &&
				this.billingFormLoaded()
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
	this.setListenerForPaymentMethodSelector = function() {
		this.spco.main_container.on( 'spco_switch_payment_methods', ( event, paymentMethod ) => {
			if ( typeof paymentMethod !== 'undefined' && paymentMethod === this.slug ) {
				this.initialize();
			} else if ( this.initialized ) {
				//and if this was previously initialized, make sure we hide the button
				this.hideSmartButtons();
			}
		} );
	};

	/**
	 * Returns true if this payment method's billing form exists on the page
	 * @return {boolean} whether it was successffully loaded or not.
	 */
	this.billingFormLoaded = function() {
		return jQuery( this.hiddenInputPaymentIdSelector ).length > 0;
	};

	/**
	 * Initializes jQuery selected objects so we don't need to query for anything afterwards
	 */
	this.initializeObjects = function() {
		this.nextButton = jQuery( this.nextButtonSelector );
		this.paymentDiv = jQuery( this.paymentDivSelector );
		this.hiddenInputPayerId = jQuery( this.hiddenInputPayerIdSelector );
		this.hiddenInputPaymentId = jQuery( this.hiddenInputPaymentIdSelector );
		this.hiddenInputPaymentToken = jQuery( this.hiddenInputPaymentTokenSelector );
		this.hiddeIinputOrderId = jQuery( this.hiddenInputOrderIdSelector );
	};

	/**
	 * Shows the smart buttons (this may require initializing them) and otherwise initializes this object
	 */
	this.initialize = function() {
		if ( typeof this.spco === 'undefined' ||
			typeof this.spco.show_event_queue_ajax_msg !== 'function' ||
			typeof this.spco.display_messages !== 'function' ||
			! this.spco.main_container ) {
			this.hideSmartButtons();
			// No SPCO object, so we can't use SPCO to show a nice error message. At least put something in the console.
			warning( false, this.translations.no_SPCO_error );
			return;
		}
		// ensure that the Paypal object (from https://www.paypalobjects.com/api/checkout.js) js class is loaded
		if ( typeof this.paypal === 'undefined' ||
			typeof this.paypal.Button !== 'object' ||
			typeof this.paypal.Button.render !== 'function' ) {
			this.spco.show_event_queue_ajax_msg( 'error', this.translations.no_paypal_js, this.spco.notice_fadeout_attention, true );
			return;
		}

		if ( ! this.initialized ) {
			this.initializeObjects();
		}
		this.showSmartButtons();
	};

	/**
	 * When the payment amount changes, just update this object's transaction_total
	 */
	this.setListenerForPaymentAmountChange = function() {
		this.spco.main_container.on( 'spco_payment_amount', ( event, paymentAmount ) => {
			if ( typeof paymentAmount !== 'undefined' && parseInt( paymentAmount ) !== 0 ) {
				this.transactionTotal = paymentAmount;
			}
		} );
	};

	/**
	 * Hide the smart buttons and show the normal "Proceed with payment" button.
	 * Done when this payment method is de-selected
	 */
	this.hideSmartButtons = function() {
		this.nextButton.show();
		if ( this.paymentDiv.length > 0 ) {
			this.paymentDiv.hide();
		}
	};

	/**
	 * Show the smart button (if it hasn't yet been initialized, initialize it)
	 * and hide the normal "proceed to finalize payment" button
	 */
	this.showSmartButtons = function() {
		if ( ! this.initialized ) {
			this.initializeSmartButtons();
		} else {
			this.paymentDiv.show();
		}
		this.nextButton.hide();
	};

	this.initializeSmartButtons = function() {
		this.initialized = true;
		//move the paypal button to the right spot
		this.paymentDiv.insertBefore( this.nextButton );
		paypal.Button.render( {

			// Set your environment

			env: this.sandboxMode ? 'sandbox' : 'production', // sandbox | production

			// Specify the style of the button
			// locale: 'en_BR',
			style: {
				layout: 'vertical', // horizontal | vertical
				size: 'responsive', // small, medium | large | responsive
				shape: this.buttonShape, // pill | rect
			},

			// Change the wording on the PayPal popup
			// See: https://github.com/paypal/paypal-checkout/issues/554
			commit: true,

			// PayPal Client IDs - replace with your own
			// Create a PayPal app: https://developer.paypal.com/developer/applications/create
			client: {
				sandbox: this.clientId,
				production: this.clientId,
			},

			payment: ( paymentData, actions ) => {
				return actions.payment.create( {
					payment: {
						//documentation on what format transactions can take: https://developer.paypal.com/docs/api/payments/#definition-transaction
						transactions: [
							{
								amount: { total: this.transactionTotal, currency: this.currency },
							},
						],
					},
					meta: {
						partner_attribution_id: 'EventEspresso_SP',
					},
				} );
			},

			onAuthorize: ( authData ) => {
				// don't execute the payment here. Let's do it server-side where it's more secure
				this.hiddenInputPayerId.val( authData.payerID );
				this.hiddenInputPaymentId.val( authData.paymentID );
				this.hiddenInputPaymentToken.val( authData.paymentToken );
				this.hiddeIinputOrderId.val( authData.orderID );
				// Wait a second before submitting in order to avoid accidentally submitting before the values were updated.
				setTimeout( () => {
					this.nextButton.trigger( 'click' );
				},
				1000 );
			},
			onError: ( errorData ) => {
				let error = null;
				if ( typeof( errorData ) !== 'undefined' && typeof( errorData.message ) !== 'undefined' ) {
					error = errorData.message;
				} else {
					error = 'An error occurred while processing payment with PayPal';
				}
				const messages = {
					errors: error,
				};
				this.spco.display_messages( messages, false );
			},

		}, this.paymentDivSelector );
	};
}

